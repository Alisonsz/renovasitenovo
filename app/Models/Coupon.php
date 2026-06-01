<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'wp_coupon_id',
        'code',
        'type',
        'amount_cents',
        'percent',
        'starts_at',
        'expires_at',
        'usage_limit',
        'used_count',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'percent' => 'decimal:2',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }
}
