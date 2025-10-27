<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionLog extends Model
{
    protected $fillable = [
        'promotion_id',
        'promotion_tier_id',
        'order_id',
        'user_id',
        'customer_name',
        'code_used',
        'discount_amount',
        'freeship_applied',
        'details',
    ];

    protected $casts = [
        'freeship_applied' => 'boolean',
        'details' => 'array',
    ];

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(PromotionTier::class, 'promotion_tier_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

