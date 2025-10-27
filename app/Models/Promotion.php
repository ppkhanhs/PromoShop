<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    protected $fillable = [
        'promo_id',
        'name',
        'type',
        'start_date',
        'end_date',
        'description',
        'min_order_amount',
        'limit_per_customer',
        'global_quota',
        'channels',
        'stackable',
        'status',
        'auto_apply',
        'max_discount_amount',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'channels' => 'array',
        'stackable' => 'boolean',
        'auto_apply' => 'boolean',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot(['discount_percent', 'discount_amount'])
            ->withTimestamps();
    }

    public function codes(): HasMany
    {
        return $this->hasMany(PromotionCode::class);
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(PromotionTier::class)->orderBy('priority');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PromotionLog::class)->latest();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
