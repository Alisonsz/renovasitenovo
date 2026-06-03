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
        // Login lives at an obscure path; the auth middleware redirects there by route name.
        $this->get('/admin')->assertRedirect('/ovodepapagaio');
    }

    public function test_old_login_path_is_gone(): void
    {
        $this->get('/login')->assertNotFound();
    }

    public function test_user_can_login_and_view_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@renovalaser.local',
            'password' => 'secret123',
        ]);
        Product::factory()->count(3)->create(['is_active' => true]);

        $this->post('/ovodepapagaio', [
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

        $this->post('/ovodepapagaio', [
            'email' => 'admin@renovalaser.local',
            'password' => 'wrong',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
