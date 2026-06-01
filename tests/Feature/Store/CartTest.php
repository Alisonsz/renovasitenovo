<?php

namespace Tests\Feature\Store;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_add_product_to_cart(): void
    {
        $product = Product::factory()->create([
            'name' => 'Combo Virilha',
            'slug' => 'combo-virilha',
            'price_cents' => 90000,
            'regular_price_cents' => 112500,
            'is_active' => true,
        ]);

        $response = $this->post('/carrinho/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertRedirect('/carrinho');
        $this->assertDatabaseHas('carts', ['subtotal_cents' => 180000, 'total_cents' => 180000]);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price_cents' => 90000,
            'total_cents' => 180000,
        ]);
    }

    public function test_adding_same_product_increments_quantity(): void
    {
        $product = Product::factory()->create([
            'price_cents' => 6000,
            'regular_price_cents' => 6000,
            'is_active' => true,
        ]);

        $this->post('/carrinho/items', ['product_id' => $product->id, 'quantity' => 1]);
        $this->post('/carrinho/items', ['product_id' => $product->id, 'quantity' => 3]);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 4,
            'total_cents' => 24000,
        ]);
        $this->assertSame(1, Cart::query()->count());
    }

    public function test_customer_can_update_and_remove_cart_item(): void
    {
        $product = Product::factory()->create([
            'price_cents' => 10000,
            'regular_price_cents' => 10000,
            'is_active' => true,
        ]);
        $this->post('/carrinho/items', ['product_id' => $product->id, 'quantity' => 2]);
        $item = Cart::query()->firstOrFail()->items()->firstOrFail();

        $this->patch("/carrinho/items/{$item->id}", ['quantity' => 3])->assertRedirect('/carrinho');
        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 3, 'total_cents' => 30000]);

        $this->delete("/carrinho/items/{$item->id}")->assertRedirect('/carrinho');
        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
        $this->assertDatabaseHas('carts', ['subtotal_cents' => 0, 'total_cents' => 0]);
    }

    public function test_cart_page_renders_current_cart(): void
    {
        $product = Product::factory()->create([
            'name' => 'Axilas',
            'price_cents' => 6000,
            'regular_price_cents' => 6000,
            'is_active' => true,
        ]);
        $this->post('/carrinho/items', ['product_id' => $product->id, 'quantity' => 1]);

        $response = $this->get('/carrinho');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Store/Cart')
            ->where('cart.items.0.product.name', 'Axilas')
            ->where('cart.subtotal_cents', 6000)
            ->where('cart.total_cents', 6000)
        );
    }
}
