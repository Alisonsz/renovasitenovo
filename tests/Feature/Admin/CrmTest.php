<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Professional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CrmTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_customer_can_be_created_with_only_a_name(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/customers', ['name' => 'Joana Cliente'])
            ->assertRedirect();

        $this->assertDatabaseHas('customers', ['name' => 'Joana Cliente', 'email' => null]);
    }

    public function test_customer_creation_requires_name(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/customers', ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_customer_full_profile_with_photo(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->post('/admin/customers', [
            'name' => 'Maria Completa',
            'email' => 'maria@example.com',
            'phone' => '11999998888',
            'document' => '12345678909',
            'birthdate' => '1990-05-20',
            'instagram' => '@maria',
            'address' => 'Rua X, 123',
            'notes' => 'Pele sensível',
            'photo' => UploadedFile::fake()->image('foto.jpg', 400, 400),
        ])->assertRedirect();

        $customer = Customer::query()->where('email', 'maria@example.com')->firstOrFail();
        $this->assertNotNull($customer->photo_path);
        $this->assertSame('@maria', $customer->instagram);
        Storage::disk('public')->assertExists($customer->photo_path);
    }

    public function test_customer_can_be_updated_and_deleted(): void
    {
        $customer = Customer::factory()->create(['name' => 'Antiga']);
        $admin = $this->admin();

        $this->actingAs($admin)->post("/admin/customers/{$customer->id}", [
            'name' => 'Nova', 'notes' => 'atualizado',
        ])->assertRedirect();
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Nova']);

        $this->actingAs($admin)->delete("/admin/customers/{$customer->id}")->assertRedirect();
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_can_associate_treatment_from_product(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'name' => '10 Sessões Axila', 'is_treatment' => true,
            'sessions_count' => 10, 'session_duration_min' => 30,
        ]);

        $this->actingAs($this->admin())->post("/admin/customers/{$customer->id}/treatments", [
            'product_id' => $product->id,
            'total_sessions' => 10,
        ])->assertRedirect();

        $this->assertDatabaseHas('treatments', [
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'total_sessions' => 10,
            'completed_sessions' => 0,
            'status' => 'active',
        ]);
    }

    public function test_can_associate_manual_treatment(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->admin())->post("/admin/customers/{$customer->id}/treatments", [
            'name' => 'Tratamento avulso',
            'total_sessions' => 5,
        ])->assertRedirect();

        $this->assertDatabaseHas('treatments', [
            'customer_id' => $customer->id,
            'name' => 'Tratamento avulso',
            'total_sessions' => 5,
        ]);
    }

    public function test_professional_crud(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/professionals', [
            'name' => 'Dra. Ana', 'role' => 'Esteticista', 'color' => '#FF0000', 'is_active' => true,
        ])->assertRedirect();
        $prof = Professional::query()->firstOrFail();
        $this->assertSame('Dra. Ana', $prof->name);

        $this->actingAs($admin)->put("/admin/professionals/{$prof->id}", [
            'name' => 'Dra. Ana Paula', 'is_active' => false,
        ])->assertRedirect();
        $this->assertSame('Dra. Ana Paula', $prof->fresh()->name);

        $this->actingAs($admin)->delete("/admin/professionals/{$prof->id}")->assertRedirect();
        $this->assertDatabaseMissing('professionals', ['id' => $prof->id]);
    }

    public function test_product_can_be_marked_as_treatment(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/products', [
            'name' => 'Pacote 5 sessões',
            'price' => '500.00',
            'regular_price' => '500.00',
            'stock_status' => 'instock',
            'is_active' => true,
            'is_treatment' => true,
            'sessions_count' => 5,
            'session_duration_min' => 45,
        ])->assertRedirect();

        $this->assertDatabaseHas('products', [
            'name' => 'Pacote 5 sessões',
            'is_treatment' => true,
            'sessions_count' => 5,
            'session_duration_min' => 45,
        ]);
    }

    public function test_non_admin_blocked_from_crm(): void
    {
        $user = User::factory()->nonAdmin()->create();
        $this->actingAs($user)->get('/admin/customers/create')->assertForbidden();
        $this->actingAs($user)->get('/admin/professionals')->assertForbidden();
    }
}
