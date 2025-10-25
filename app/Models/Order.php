<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = [
        'order_code',
        'customer_name',
        'customer_phone',
        'promotion_id',
        'total_amount',
        'discount_amount',
        'final_amount',
        'order_date',
        'status',
    ];

    protected $casts = [
        'order_date' => 'date',
    ];

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }
}
