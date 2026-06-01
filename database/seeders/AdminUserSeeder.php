<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@renovalaser.local');
        $password = env('ADMIN_PASSWORD', 'change-me');

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Renova Admin'),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );
    }
}
