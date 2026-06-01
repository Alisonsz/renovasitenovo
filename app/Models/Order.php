<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'number',
        'user_id',
        'customer_id',
        'cart_id',
        'coupon_id',
        'status',
        'payment_status',
        'payment_method',
        'subtotal_cents',
        'discount_cents',
        'pix_discount_cents',
        'total_cents',
        'currency',
        'pagbank_checkout_id',
        'pagbank_order_id',
        'pagbank_pay_url',
        'paid_at',
        'cancelled_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_cents' => 'integer',
            'discount_cents' => 'integer',
            'pix_discount_cents' => 'integer',
            'total_cents' => 'integer',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'metadata' => 'array',
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

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }
}
