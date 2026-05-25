<?php

namespace Mhmfajar\PaymentOrchestratorLaravel\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Optional Eloquent model for inspecting individual gateway payment attempts.
 */
class PaymentAttempt extends Model
{
    /**
     * Allow applications to extend attempt columns without updating the model.
     *
     * @var array
     */
    protected $guarded = array();

    /**
     * Cast JSON and boolean columns to native PHP values.
     *
     * @var array
     */
    protected $casts = array(
        'is_active' => 'boolean',
        'raw_request' => 'array',
        'raw_response' => 'array',
    );
}
