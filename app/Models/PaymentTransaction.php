<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'order_id',
        'provider',
        'provider_transaction_id',
        'provider_checkout_id',
        'method',
        'status',
        'amount_cents',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'raw_payload' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
