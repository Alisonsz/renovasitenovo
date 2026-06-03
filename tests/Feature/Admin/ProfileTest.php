<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_loads(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/minha-conta')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Admin/Profile/Edit'));
    }

    public function test_admin_can_update_name_and_email(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret123')]);

        $this->actingAs($user)->put('/admin/minha-conta', [
            'name' => 'Novo Nome',
            'email' => 'novo@renova.com',
            'current_password' => 'secret123',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id, 'name' => 'Novo Nome', 'email' => 'novo@renova.com',
        ]);
    }

    public function test_email_update_requires_correct_current_password(): void
    {
        $user = User::factory()->create(['email' => 'old@renova.com', 'password' => Hash::make('secret123')]);

        $this->actingAs($user)->put('/admin/minha-conta', [
            'name' => 'X', 'email' => 'hacker@renova.com', 'current_password' => 'wrong',
        ])->assertSessionHasErrors('current_password');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'old@renova.com']);
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'taken@renova.com']);
        $user = User::factory()->create(['password' => Hash::make('secret123')]);

        $this->actingAs($user)->put('/admin/minha-conta', [
            'name' => 'X', 'email' => 'taken@renova.com', 'current_password' => 'secret123',
        ])->assertSessionHasErrors('email');
    }

    public function test_admin_can_change_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret123')]);

        $this->actingAs($user)->put('/admin/minha-conta/senha', [
            'current_password' => 'secret123',
            'password' => 'novaSenha456',
            'password_confirmation' => 'novaSenha456',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('novaSenha456', $user->fresh()->password));
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret123')]);

        $this->actingAs($user)->put('/admin/minha-conta/senha', [
            'current_password' => 'wrong',
            'password' => 'novaSenha456',
            'password_confirmation' => 'novaSenha456',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('secret123', $user->fresh()->password));
    }

    public function test_password_change_requires_confirmation_and_min_length(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret123')]);

        $this->actingAs($user)->put('/admin/minha-conta/senha', [
            'current_password' => 'secret123',
            'password' => 'short',
            'password_confirmation' => 'mismatch',
        ])->assertSessionHasErrors('password');
    }

    public function test_non_admin_blocked(): void
    {
        $this->actingAs(User::factory()->nonAdmin()->create())
            ->get('/admin/minha-conta')->assertForbidden();
    }
}
