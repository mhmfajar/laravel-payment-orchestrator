# Laravel Payment Orchestrator Adapter

<!-- Laravel adapter usage notes for service provider, facade, config, and migrations. -->

Install:

```bash
composer require mhmfajar/laravel-payment-orchestrator
php artisan vendor:publish --tag=payment-orchestrator-config
php artisan vendor:publish --tag=payment-orchestrator-migrations
php artisan migrate
```

Usage:

```php
use Mhmfajar\PaymentOrchestratorLaravel\Facades\Payment;

$response = Payment::create(array(
    'order_id' => 'INV-001',
    'amount' => 150000,
    'customer_name' => 'Mhmfajar',
    'customer_email' => 'mhmfajar@example.com',
));

return redirect($response->getPaymentUrl());
```
