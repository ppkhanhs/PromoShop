<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voucher extends Model
{
    protected $fillable = [
        'promotion_id',
        'code',
        'label',
        'description',
        'status',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'min_order',
        'start_date',
        'end_date',
        'usage_limit_total',
        'usage_limit_per_user',
        'usage_count',
        'first_order_only',
        'new_user_only',
        'customer_tier',
        'channels',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'channels' => 'array',
        'metadata' => 'array',
        'first_order_only' => 'boolean',
        'new_user_only' => 'boolean',
    ];

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

}
