<?php

namespace Mhmfajar\PaymentOrchestratorLaravel\Storage;

use Illuminate\Database\ConnectionInterface;
use Mhmfajar\PaymentOrchestrator\Constants\PaymentStatus;
use Mhmfajar\PaymentOrchestrator\Contracts\PaymentStoreInterface;
use Mhmfajar\PaymentOrchestrator\DTO\PaymentRequest;
use Mhmfajar\PaymentOrchestrator\DTO\PaymentResponse;

/**
 * Laravel database store implementing the framework-agnostic core storage contract.
 */
class EloquentPaymentStore implements PaymentStoreInterface
{
    /**
     * Laravel database connection.
     *
     * @var ConnectionInterface
     */
    private $db;

    /**
     * Table mapping for payments and attempts.
     *
     * @var array
     */
    private $tables;

    /**
     * Create a Laravel database-backed payment store.
     *
     * @param ConnectionInterface $db Laravel database connection.
     * @param array $tables Table name mapping.
     * @return void
     */
    public function __construct(ConnectionInterface $db, array $tables = array())
    {
        $this->db = $db;
        $this->tables = array(
            'payments' => isset($tables['payments']) ? $tables['payments'] : 'payments',
            'payment_attempts' => isset($tables['payment_attempts']) ? $tables['payment_attempts'] : 'payment_attempts',
        );
    }

    /**
     * Find one payment row by application order ID.
     *
     * @param string $orderId Application order identifier.
     * @return array|null Payment row.
     */
    public function findPaymentByOrderId($orderId)
    {
        $payment = $this->db->table($this->tables['payments'])->where('order_id', $orderId)->first();
        return $payment ? (array) $payment : null;
    }

    /**
     * Insert a payment row from a normalized request.
     *
     * @param PaymentRequest $request Normalized payment request.
     * @return array Created payment row.
     */
    public function createPayment(PaymentRequest $request)
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table($this->tables['payments'])->insert(array(
            'order_id' => $request->getOrderId(),
            'amount' => $request->getAmount(),
            'currency' => $request->getCurrency(),
            'status' => PaymentStatus::PENDING,
            'customer_name' => $request->getCustomerName(),
            'customer_email' => $request->getCustomerEmail(),
            'customer_phone' => $request->getCustomerPhone(),
            'items' => json_encode($request->getItems()),
            'metadata' => json_encode($request->getMetadata()),
            'created_at' => $now,
            'updated_at' => $now,
        ));

        return $this->findPaymentByOrderId($request->getOrderId());
    }

    /**
     * Update payment status and supported mutable columns.
     *
     * @param string $orderId Application order identifier.
     * @param string $status Universal payment status.
     * @param array $data Additional payment update data.
     * @return array|null Updated payment row.
     */
    public function updatePaymentStatus($orderId, $status, array $data = array())
    {
        $updates = array('status' => $status, 'updated_at' => date('Y-m-d H:i:s'));
        if (isset($data['active_gateway'])) {
            $updates['active_gateway'] = $data['active_gateway'];
        }
        if ($status === PaymentStatus::PAID) {
            $updates['paid_at'] = date('Y-m-d H:i:s');
        }

        $this->db->table($this->tables['payments'])->where('order_id', $orderId)->update($updates);

        return $this->findPaymentByOrderId($orderId);
    }

    /**
     * Return the active attempt for an order, if one exists.
     *
     * @param string $orderId Application order identifier.
     * @return array|null Active attempt row.
     */
    public function findActiveAttempt($orderId)
    {
        $payment = $this->findPaymentByOrderId($orderId);
        if (! $payment) {
            return null;
        }

        $attempt = $this->db->table($this->tables['payment_attempts'])->where('payment_id', $payment['id'])->where('is_active', 1)->orderBy('id', 'desc')->first();

        return $attempt ? (array) $attempt : null;
    }

    /**
     * Insert a gateway attempt row.
     *
     * @param string $orderId Application order identifier.
     * @param string $gateway Gateway name.
     * @param PaymentRequest $request Normalized payment request.
     * @param PaymentResponse $response Normalized gateway response.
     * @return array Created attempt row.
     */
    public function createAttempt($orderId, $gateway, PaymentRequest $request, PaymentResponse $response)
    {
        $payment = $this->findPaymentByOrderId($orderId);
        if (! $payment) {
            $payment = $this->createPayment($request);
        }

        $now = date('Y-m-d H:i:s');
        $id = $this->db->table($this->tables['payment_attempts'])->insertGetId(array(
            'payment_id' => $payment['id'],
            'gateway' => $gateway,
            'gateway_order_id' => $response->getGatewayOrderId(),
            'gateway_transaction_id' => $response->getTransactionId(),
            'status' => $response->getStatus() ?: ($response->isSuccess() ? PaymentStatus::PENDING : PaymentStatus::FAILED),
            'payment_url' => $response->getPaymentUrl(),
            'qr_string' => $response->getQrString(),
            'va_number' => $response->getVaNumber(),
            'error_message' => $response->getMessage(),
            'failure_reason' => $response->getFailureReason(),
            'is_active' => 0,
            'raw_request' => json_encode(array('order_id' => $request->getOrderId(), 'amount' => $request->getAmount())),
            'raw_response' => json_encode($response->getRaw()),
            'created_at' => $now,
            'updated_at' => $now,
        ));

        return (array) $this->db->table($this->tables['payment_attempts'])->where('id', $id)->first();
    }

    /**
     * Mark one attempt active and deactivate sibling attempts.
     *
     * @param int|string $attemptId Attempt identifier.
     * @return array|null Updated attempt row.
     */
    public function markAttemptAsActive($attemptId)
    {
        $attempt = $this->db->table($this->tables['payment_attempts'])->where('id', $attemptId)->first();
        if (! $attempt) {
            return null;
        }

        // Keep a single active attempt per payment before exposing it back to the core manager.
        $this->db->table($this->tables['payment_attempts'])->where('payment_id', $attempt->payment_id)->update(array('is_active' => 0));
        $this->db->table($this->tables['payment_attempts'])->where('id', $attemptId)->update(array('is_active' => 1, 'updated_at' => date('Y-m-d H:i:s')));

        return (array) $this->db->table($this->tables['payment_attempts'])->where('id', $attemptId)->first();
    }

    /**
     * Update attempt status and selected gateway result columns.
     *
     * @param int|string $attemptId Attempt identifier.
     * @param string $status Universal payment status.
     * @param array $data Additional attempt update data.
     * @return array|null Updated attempt row.
     */
    public function updateAttemptStatus($attemptId, $status, array $data = array())
    {
        // $updates contains only columns the adapter intentionally allows to change.
        $updates = array('status' => $status, 'updated_at' => date('Y-m-d H:i:s'));

        foreach (array('gateway_order_id', 'gateway_transaction_id', 'payment_url', 'qr_string', 'va_number', 'error_code', 'error_message', 'failure_reason', 'raw_response') as $key) {
            if (array_key_exists($key, $data)) {
                $updates[$key] = is_array($data[$key]) ? json_encode($data[$key]) : $data[$key];
            }
        }

        $this->db->table($this->tables['payment_attempts'])->where('id', $attemptId)->update($updates);

        return (array) $this->db->table($this->tables['payment_attempts'])->where('id', $attemptId)->first();
    }
}
