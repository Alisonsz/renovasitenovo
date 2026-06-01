<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_user_can_login_and_view_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@renovalaser.local',
            'password' => 'secret123',
        ]);
        Product::factory()->count(3)->create(['is_active' => true]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
        $this->get('/admin')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->where('metrics.products', 3)
        );
    }

    public function test_invalid_login_shows_validation_error(): void
    {
        User::factory()->create([
            'email' => 'admin@renovalaser.local',
            'password' => 'secret123',
        ]);

        $this->post('/login', [
            'email' => 'admin@renovalaser.local',
            'password' => 'wrong',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
