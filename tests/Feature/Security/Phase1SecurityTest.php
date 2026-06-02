<?php

namespace Tests\Feature\Security;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_user_is_forbidden_from_admin(): void
    {
        $user = User::factory()->nonAdmin()->create();

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_admin_user_can_reach_admin(): void
    {
        $user = User::factory()->create(); // admin by default

        $this->actingAs($user)->get('/admin')->assertOk();
    }

    public function test_cannot_mutate_a_cart_item_from_another_cart(): void
    {
        $product = Product::factory()->create(['price_cents' => 10000, 'is_active' => true]);

        // Victim cart with an item, owned by a different session (no session uuid here).
        $victimCart = Cart::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'status' => 'active',
        ]);
        $victimItem = CartItem::query()->create([
            'cart_id' => $victimCart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price_cents' => 10000,
            'total_cents' => 10000,
        ]);

        // Attacker has their own (empty) session cart, tries to edit the victim's item by id.
        $this->post('/carrinho/items', ['product_id' => $product->id, 'quantity' => 1]);

        $this->patch("/carrinho/items/{$victimItem->id}", ['quantity' => 99])->assertNotFound();
        $this->delete("/carrinho/items/{$victimItem->id}")->assertNotFound();

        $this->assertDatabaseHas('cart_items', ['id' => $victimItem->id, 'quantity' => 1]);
    }

    public function test_webhook_rejects_invalid_authenticity_token(): void
    {
        // With a token configured, the signature is checked regardless of env.
        config()->set('services.pagbank.webhook_token', 'secret-token');

        $this->postJson('/webhooks/pagbank', [
            'reference_id' => 'RL-X', 'status' => 'PAID',
        ], ['x-authenticity-token' => 'wrong'])->assertStatus(401);
    }

    public function test_webhook_accepts_valid_authenticity_token(): void
    {
        config()->set('services.pagbank.webhook_token', 'secret-token');

        $customer = \App\Models\Customer::query()->create([
            'name' => 'M', 'email' => 'm@e.com', 'phone' => '1', 'document' => '1',
        ]);
        $order = \App\Models\Order::query()->create([
            'number' => 'RL-20260601-000001',
            'customer_id' => $customer->id,
            'status' => 'pending', 'payment_status' => 'pending',
            'subtotal_cents' => 9000, 'total_cents' => 9000,
            'pagbank_checkout_id' => 'CHEC_1',
        ]);

        $body = json_encode([
            'id' => 'CHEC_1',
            'reference_id' => $order->number,
            'status' => 'PAID',
            'charges' => [['id' => 'CHAR_1', 'status' => 'PAID', 'amount' => ['value' => 9000], 'payment_method' => ['type' => 'PIX']]],
        ]);
        $sig = hash('sha256', 'secret-token-'.$body);

        $this->call('POST', '/webhooks/pagbank', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_AUTHENTICITY_TOKEN' => $sig,
        ], $body)->assertNoContent();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'paid']);
    }

    public function test_checkout_cannot_be_replayed_on_a_converted_cart(): void
    {
        $product = Product::factory()->create([
            'price_cents' => 6000, 'regular_price_cents' => 6000, 'is_active' => true,
        ]);
        $this->post('/carrinho/items', ['product_id' => $product->id, 'quantity' => 1]);

        $payload = [
            'name' => 'Maria', 'email' => 'maria@example.com',
            'phone' => '11999999999', 'document' => '12345678909',
            'payment_method' => 'pagbank_checkout',
        ];

        $this->post('/checkout', $payload)->assertRedirect();
        $this->assertSame(1, \App\Models\Order::query()->count());

        // Second submit should NOT create another order (cart converted + session cleared).
        $this->post('/checkout', $payload);
        $this->assertSame(1, \App\Models\Order::query()->count());
    }

    public function test_managed_stock_blocks_overselling(): void
    {
        $product = Product::factory()->create([
            'price_cents' => 5000, 'is_active' => true,
            'manage_stock' => true, 'stock_quantity' => 2,
        ]);

        $this->post('/carrinho/items', ['product_id' => $product->id, 'quantity' => 2])
            ->assertRedirect('/carrinho');

        // Adding a 3rd exceeds stock -> validation error, quantity stays 2.
        $this->from('/carrinho')
            ->post('/carrinho/items', ['product_id' => $product->id, 'quantity' => 1])
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseHas('cart_items', ['product_id' => $product->id, 'quantity' => 2]);
    }

    public function test_stock_is_decremented_on_order(): void
    {
        $product = Product::factory()->create([
            'price_cents' => 5000, 'is_active' => true,
            'manage_stock' => true, 'stock_quantity' => 5,
        ]);
        $this->post('/carrinho/items', ['product_id' => $product->id, 'quantity' => 2]);

        $this->post('/checkout', [
            'name' => 'Maria', 'email' => 'maria@example.com',
            'phone' => '11999999999', 'document' => '12345678909',
            'payment_method' => 'pagbank_checkout',
        ])->assertRedirect();

        $this->assertSame(3, $product->fresh()->stock_quantity);
    }
}
