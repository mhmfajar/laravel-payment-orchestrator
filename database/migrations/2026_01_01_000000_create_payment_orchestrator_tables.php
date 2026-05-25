<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the default Laravel tables used by the payment orchestrator store.
 */
class CreatePaymentOrchestratorTables extends Migration
{
    /**
     * Create payments and attempts tables.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('order_id')->unique();
            $table->unsignedBigInteger('amount');
            $table->string('currency', 10)->default('IDR');
            $table->string('status', 50)->default('pending');
            $table->string('active_gateway', 100)->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone', 50)->nullable();
            $table->json('items')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('payment_id');
            $table->string('gateway', 100);
            $table->string('gateway_order_id')->nullable();
            $table->string('gateway_transaction_id')->nullable();
            $table->string('status', 50)->default('pending');
            $table->text('payment_url')->nullable();
            $table->text('qr_string')->nullable();
            $table->string('va_number')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->string('failure_reason')->nullable();
            $table->boolean('is_active')->default(false);
            $table->json('raw_request')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
            $table->index(array('payment_id', 'gateway'));
            $table->index(array('gateway', 'gateway_order_id'));
            $table->index('status');
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade');
        });
    }

    /**
     * Drop attempts first because they reference payments.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_attempts');
        Schema::dropIfExists('payments');
    }
}
