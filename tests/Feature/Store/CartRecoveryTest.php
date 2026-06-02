<?php

namespace Tests\Feature\Store;

use App\Mail\AbandonedCartMail;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CartRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private function seedCartWithItem(int $priceCents = 90000): Product
    {
        $product = Product::factory()->create([
            'name' => 'Combo Virilha',
            'price_cents' => $priceCents,
            'regular_price_cents' => $priceCents,
            'is_active' => true,
        ]);
        $this->post('/carrinho/items', ['product_id' => $product->id, 'quantity' => 1]);

        return $product;
    }

    public function test_checkout_captures_email_first(): void
    {
        $this->seedCartWithItem();

        $this->post('/checkout/identificacao', [
            'email' => 'Maria@Example.com',
            'name' => 'Maria',
        ])->assertRedirect();

        $this->assertDatabaseHas('carts', [
            'email' => 'maria@example.com', // normalized lowercase
            'customer_name' => 'Maria',
        ]);
    }

    public function test_email_capture_requires_items(): void
    {
        // fresh cart (created on first touch), but empty
        $this->get('/carrinho');

        $this->post('/checkout/identificacao', ['email' => 'a@b.com'])
            ->assertSessionHasErrors('cart');
    }

    public function test_abandoned_cart_command_queues_recovery_with_coupon(): void
    {
        Mail::fake();
        $this->seedCartWithItem();
        $this->post('/checkout/identificacao', ['email' => 'maria@example.com', 'name' => 'Maria']);

        // Make the cart look inactive enough to be abandoned.
        $cart = Cart::query()->firstOrFail();
        $cart->forceFill(['last_activity_at' => now()->subHours(2)])->save();

        $this->artisan('cart:send-recovery')->assertExitCode(0);

        $cart->refresh();
        $this->assertSame('abandoned', $cart->status);
        $this->assertNotNull($cart->recovery_token);
        $this->assertNotNull($cart->recovery_coupon_id);
        $this->assertNotNull($cart->recovery_email_sent_at);

        Mail::assertQueued(AbandonedCartMail::class, fn ($m) => $m->hasTo('maria@example.com'));

        // The minted coupon is a valid percent coupon.
        $this->assertDatabaseHas('coupons', [
            'id' => $cart->recovery_coupon_id,
            'type' => 'percent',
            'is_active' => true,
        ]);
    }

    public function test_recovery_is_not_sent_twice(): void
    {
        Mail::fake();
        $this->seedCartWithItem();
        $this->post('/checkout/identificacao', ['email' => 'maria@example.com']);
        Cart::query()->firstOrFail()->forceFill(['last_activity_at' => now()->subHours(2)])->save();

        $this->artisan('cart:send-recovery');
        $this->artisan('cart:send-recovery'); // second run

        Mail::assertQueued(AbandonedCartMail::class, 1);
    }

    public function test_recovery_link_restores_cart_and_applies_coupon(): void
    {
        Mail::fake();
        $this->seedCartWithItem(90000);
        $this->post('/checkout/identificacao', ['email' => 'maria@example.com']);
        $cart = Cart::query()->firstOrFail();
        $cart->forceFill(['last_activity_at' => now()->subHours(2)])->save();
        $this->artisan('cart:send-recovery');

        $cart->refresh();
        $token = $cart->recovery_token;

        // Simulate a different session opening the recovery link.
        $this->flushSession();
        $this->get("/carrinho/recuperar/{$token}")->assertRedirect('/carrinho');

        $cart->refresh();
        $this->assertSame('recovered', $cart->status);
        $this->assertNotNull($cart->recovered_at);
        $this->assertSame($cart->recovery_coupon_id, $cart->coupon_id); // coupon applied
        $this->assertGreaterThan(0, $cart->discount_cents);
    }

    public function test_invalid_recovery_token_redirects_gracefully(): void
    {
        $this->get('/carrinho/recuperar/nonexistenttoken')->assertRedirect('/carrinho');
    }

    public function test_active_recent_cart_is_not_recovered(): void
    {
        Mail::fake();
        $this->seedCartWithItem();
        $this->post('/checkout/identificacao', ['email' => 'maria@example.com']);
        // last_activity_at is "now" — not abandoned yet.

        $this->artisan('cart:send-recovery');

        Mail::assertNothingQueued();
        $this->assertSame('active', Cart::query()->firstOrFail()->status);
    }
}
