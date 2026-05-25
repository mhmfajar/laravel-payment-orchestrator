<?php

namespace Mhmfajar\PaymentOrchestratorLaravel;

use Illuminate\Support\ServiceProvider;
use Mhmfajar\PaymentOrchestrator\PaymentOrchestrator;
use Mhmfajar\PaymentOrchestratorLaravel\Storage\EloquentPaymentStore;

/**
 * Registers the core orchestrator in Laravel's container with an Eloquent-backed store.
 */
class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Bind the orchestrator singleton into Laravel's container.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/payment-orchestrator.php', 'payment-orchestrator');

        $this->app->singleton('payment-orchestrator', function ($app) {
            $config = $app['config']->get('payment-orchestrator');
            $store = new EloquentPaymentStore($app['db']->connection(), isset($config['tables']) ? $config['tables'] : array());

            return PaymentOrchestrator::make($config)->setStore($store);
        });
    }

    /**
     * Register publishable config and migration resources.
     *
     * @return void
     */
    public function boot()
    {
        // Publish config and migrations separately so applications can opt into each artifact.
        $this->publishes(array(
            __DIR__ . '/../config/payment-orchestrator.php' => config_path('payment-orchestrator.php'),
        ), 'payment-orchestrator-config');

        $this->publishes(array(
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ), 'payment-orchestrator-migrations');
    }
}
