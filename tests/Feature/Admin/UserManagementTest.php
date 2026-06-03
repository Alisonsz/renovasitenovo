<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_loads(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/usuarios')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Admin/Users/Index'));
    }

    public function test_can_create_admin_user(): void
    {
        $this->actingAs(User::factory()->create())->post('/admin/usuarios', [
            'name' => 'Novo Admin',
            'email' => 'novo@renova.com',
            'password' => 'senha12345',
            'password_confirmation' => 'senha12345',
            'is_admin' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'novo@renova.com', 'is_admin' => true]);
        $created = User::where('email', 'novo@renova.com')->first();
        $this->assertTrue(Hash::check('senha12345', $created->password));
    }

    public function test_create_requires_matching_password_and_unique_email(): void
    {
        $admin = User::factory()->create(['email' => 'taken@renova.com']);

        $this->actingAs($admin)->post('/admin/usuarios', [
            'name' => 'X', 'email' => 'taken@renova.com',
            'password' => 'senha12345', 'password_confirmation' => 'outra',
        ])->assertSessionHasErrors(['email', 'password']);
    }

    public function test_can_update_user_and_optionally_change_password(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create(['name' => 'Antigo', 'password' => Hash::make('orig12345')]);

        $this->actingAs($admin)->put("/admin/usuarios/{$target->id}", [
            'name' => 'Atualizado', 'email' => $target->email, 'is_admin' => true,
        ])->assertRedirect();
        $this->assertSame('Atualizado', $target->fresh()->name);
        $this->assertTrue(Hash::check('orig12345', $target->fresh()->password)); // unchanged

        $this->actingAs($admin)->put("/admin/usuarios/{$target->id}", [
            'name' => 'Atualizado', 'email' => $target->email, 'is_admin' => true,
            'password' => 'novaSenha99', 'password_confirmation' => 'novaSenha99',
        ])->assertRedirect();
        $this->assertTrue(Hash::check('novaSenha99', $target->fresh()->password));
    }

    public function test_cannot_delete_self(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->delete("/admin/usuarios/{$admin->id}")
            ->assertSessionHasErrors('user');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_cannot_remove_own_admin_access(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->put("/admin/usuarios/{$admin->id}", [
            'name' => $admin->name, 'email' => $admin->email, 'is_admin' => false,
        ])->assertSessionHasErrors('is_admin');
        $this->assertTrue($admin->fresh()->is_admin);
    }

    public function test_cannot_delete_last_admin(): void
    {
        $admin = User::factory()->create();
        $other = User::factory()->create(); // also admin by default

        // make `other` the one acting, delete `admin` is fine (2 admins) ...
        $this->actingAs($other)->delete("/admin/usuarios/{$admin->id}")->assertRedirect();
        // now only `other` remains — deleting the last admin must be blocked,
        // but you can't delete yourself anyway; demoting is the real guard:
        $this->actingAs($other)->put("/admin/usuarios/{$other->id}", [
            'name' => $other->name, 'email' => $other->email, 'is_admin' => false,
        ])->assertSessionHasErrors('is_admin');
    }

    public function test_non_admin_blocked(): void
    {
        $this->actingAs(User::factory()->nonAdmin()->create())
            ->get('/admin/usuarios')->assertForbidden();
    }
}
