<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'category',
        'price',
        'image_url',
        'status',
    ];

    protected $casts = [
        'price' => 'integer',
    ];

    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class)
            ->withPivot(['discount_percent', 'discount_amount'])
            ->withTimestamps();
    }
}
