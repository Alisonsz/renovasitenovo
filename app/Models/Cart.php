<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'customer_id',
        'email',
        'customer_name',
        'status',
        'recovery_token',
        'coupon_id',
        'recovery_coupon_id',
        'subtotal_cents',
        'discount_cents',
        'total_cents',
        'expires_at',
        'last_activity_at',
        'recovery_email_sent_at',
        'recovered_at',
        'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_cents' => 'integer',
            'discount_cents' => 'integer',
            'total_cents' => 'integer',
            'expires_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'recovery_email_sent_at' => 'datetime',
            'recovered_at' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function recoveryCoupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'recovery_coupon_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}
