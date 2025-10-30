<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVoucher extends Model
{
    protected $table = 'product_voucher';

    protected $fillable = [
        'product_id',
        'voucher_code',
        'promo_id',
        'discount_type',
        'discount_value',
        'max_discount_amount',
    ];
}
