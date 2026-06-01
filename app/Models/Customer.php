<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'document',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
