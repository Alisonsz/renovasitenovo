<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $fillable = [
        'customer_id', 'professional_id', 'treatment_id',
        'starts_at', 'ends_at', 'session_number', 'status', 'notes',
        'reminder_sent_at', 'confirmed_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'session_number' => 'integer',
            'reminder_sent_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }

    public function scopeBetween(Builder $query, $start, $end): Builder
    {
        return $query->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['cancelled']);
    }
}
