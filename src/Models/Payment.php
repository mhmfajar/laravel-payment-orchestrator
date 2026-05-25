<?php

namespace Mhmfajar\PaymentOrchestratorLaravel\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Optional Eloquent model for applications that want to extend payment records.
 */
class Payment extends Model
{
    /**
     * Allow applications to extend payment columns without updating the model.
     *
     * @var array
     */
    protected $guarded = array();

    /**
     * Cast JSON columns to arrays.
     *
     * @var array
     */
    protected $casts = array(
        'items' => 'array',
        'metadata' => 'array',
    );
}
