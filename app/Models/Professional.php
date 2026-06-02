<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Professional extends Model
{
    protected $fillable = [
        'name', 'role', 'color', 'phone', 'email', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
