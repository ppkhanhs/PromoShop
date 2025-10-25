<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionProduct extends Model
{
    protected $table = 'promotion_product';

    protected $fillable = [
        'promotion_id',
        'product_id',
        'discount_percent',
        'discount_amount',
    ];

    protected $casts = [
        'discount_percent' => 'integer',
        'discount_amount' => 'integer',
    ];

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
