<?php

namespace Tests\Feature\Store;

use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_apply_coupon_to_cart_and_checkout_counts_usage(): void
    {
        $product = Product::factory()->create([
            'name' => 'Combo Virilha',
            'price_cents' => 100000,
            'regular_price_cents' => 100000,
            'is_active' => true,
        ]);
        $coupon = Coupon::query()->create([
            'code' => 'renova10',
            'type' => 'percent',
            'percent' => 10,
            'is_active' => true,
        ]);

        $this->post('/carrinho/items', ['product_id' => $product->id, 'quantity' => 1]);
        $this->post('/carrinho/cupom', ['coupon' => 'RENOVA10'])->assertRedirect('/carrinho');

        $this->get('/carrinho')->assertInertia(fn ($page) => $page
            ->component('Store/Cart')
            ->where('cart.coupon.code', 'renova10')
            ->where('cart.discount_cents', 10000)
            ->where('cart.total_cents', 90000)
        );

        $this->post('/checkout', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '11999999999',
            'document' => '12345678909',
            'payment_method' => 'pagbank_checkout',
        ])->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'coupon_id' => $coupon->id,
            'discount_cents' => 10000,
            'total_cents' => 90000,
        ]);
        $this->assertSame(1, $coupon->fresh()->used_count);
    }

    public function test_invalid_coupon_returns_validation_error(): void
    {
        $product = Product::factory()->create(['is_active' => true]);

        $this->post('/carrinho/items', ['product_id' => $product->id, 'quantity' => 1]);
        $this->post('/carrinho/cupom', ['coupon' => 'naoexiste'])
            ->assertSessionHasErrors('coupon');
    }
}
