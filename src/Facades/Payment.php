<?php

namespace Mhmfajar\PaymentOrchestratorLaravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Laravel facade for the payment orchestrator service binding.
 */
class Payment extends Facade
{
    /**
     * Return the Laravel container binding used by the facade.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'payment-orchestrator';
    }
}
