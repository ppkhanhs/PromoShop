<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionCode extends Model
{
    protected $fillable = [
        'code',
        'promotion_id',
        'description',
        'start_date',
        'end_date',
        'enabled',
        'usage_limit',
        'usage_count',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'enabled' => 'boolean',
    ];

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }
}
