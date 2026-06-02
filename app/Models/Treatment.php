<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Treatment extends Model
{
    protected $fillable = [
        'customer_id', 'product_id', 'order_id', 'order_item_id',
        'name', 'total_sessions', 'completed_sessions', 'session_duration_min',
        'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_sessions' => 'integer',
            'completed_sessions' => 'integer',
            'session_duration_min' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function remainingSessions(): int
    {
        return max(0, $this->total_sessions - $this->completed_sessions);
    }

    /** Recompute completed_sessions from completed appointments and close if done. */
    public function syncProgress(): void
    {
        $done = $this->appointments()->where('status', 'completed')->count();

        $this->forceFill([
            'completed_sessions' => $done,
            'status' => $done >= $this->total_sessions ? 'completed' : 'active',
        ])->save();
    }
}
