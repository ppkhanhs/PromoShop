<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromotionTier extends Model
{
    protected $fillable = [
        'promotion_id',
        'priority',
        'label',
        'min_order_amount',
        'min_quantity',
        'discount_percent',
        'discount_amount',
        'freeship',
        'gift_product_id',
        'gift_quantity',
        'combo_description',
        'metadata',
    ];

    protected $casts = [
        'freeship' => 'boolean',
        'metadata' => 'array',
    ];

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function giftProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'gift_product_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PromotionLog::class);
    }
}

