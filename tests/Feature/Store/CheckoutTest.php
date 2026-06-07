<?php

namespace Tests\Feature\Store;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_order_from_cart(): void
    {
        $product = Product::factory()->create([
            'name' => 'Combo Virilha',
            'slug' => 'combo-virilha',
            'price_cents' => 90000,
            'regular_price_cents' => 112500,
            'is_active' => true,
        ]);
        $this->post('/carrinho/items', ['product_id' => $product->id, 'quantity' => 2]);

        $response = $this->post('/checkout', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '11999999999',
            'document' => '12345678909',
            'payment_method' => 'pagbank_checkout',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('customers', [
            'email' => 'maria@example.com',
            'document' => '12345678909',
        ]);
        $this->assertDatabaseHas('orders', [
            'subtotal_cents' => 180000,
            'total_cents' => 180000,
            'payment_method' => 'pagbank_checkout',
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
        $this->assertDatabaseHas('order_items', [
            'product_name' => 'Combo Virilha',
            'quantity' => 2,
            'unit_price_cents' => 90000,
            'total_cents' => 180000,
        ]);
    }

    public function test_validation_messages_are_in_portuguese(): void
    {
        $this->post('/checkout', [
            'email' => 'maria@example.com',
            'phone' => '11999999999',
            'document' => '12345678909',
            'payment_method' => 'pix',
        ])->assertSessionHasErrors(['name' => 'O campo nome é obrigatório.']);
    }

    public function test_checkout_requires_items_in_cart(): void
    {
        $response = $this->post('/checkout', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '11999999999',
            'document' => '12345678909',
            'payment_method' => 'pagbank_checkout',
        ]);

        $response->assertSessionHasErrors('cart');
    }

    public function test_payment_return_page_renders_order_state(): void
    {
        $product = Product::factory()->create([
            'price_cents' => 6000,
            'regular_price_cents' => 6000,
            'is_active' => true,
        ]);
        $this->post('/carrinho/items', ['product_id' => $product->id, 'quantity' => 1]);
        $this->post('/checkout', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '11999999999',
            'document' => '12345678909',
            'payment_method' => 'pagbank_checkout',
        ]);
        $number = Order::query()->firstOrFail()->number;

        $response = $this->get("/pedido/{$number}/retorno");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Store/PaymentReturn')
            ->where('order.number', $number)
            ->where('order.payment_status', 'pending')
        );
    }

    public function test_checkout_creates_pagbank_checkout_when_token_is_configured(): void
    {
        config()->set('services.pagbank.env', 'sandbox');
        config()->set('services.pagbank.token', 'test-token');
        config()->set('services.pagbank.redirect_base_url', 'https://renova.test');
        config()->set('services.pagbank.notification_url', 'https://renova.test/webhooks/pagbank');

        Http::fake([
            'https://sandbox.api.pagseguro.com/checkouts' => Http::response([
                'id' => 'CHEC_TEST_123',
                'links' => [
                    [
                        'rel' => 'PAY',
                        'href' => 'https://pagamento.sandbox.pagbank.com.br/pagamento?code=CHEC_TEST_123',
                    ],
                ],
            ], 201),
        ]);

        $product = Product::factory()->create([
            'name' => 'Combo Virilha',
            'price_cents' => 90000,
            'regular_price_cents' => 112500,
            'is_active' => true,
        ]);
        $this->post('/carrinho/items', ['product_id' => $product->id, 'quantity' => 1]);

        $response = $this->post('/checkout', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '11999999999',
            'document' => '12345678909',
            'payment_method' => 'pagbank_checkout',
        ]);

        $response->assertRedirect('https://pagamento.sandbox.pagbank.com.br/pagamento?code=CHEC_TEST_123');
        $this->assertDatabaseHas('orders', [
            'pagbank_checkout_id' => 'CHEC_TEST_123',
            'pagbank_pay_url' => 'https://pagamento.sandbox.pagbank.com.br/pagamento?code=CHEC_TEST_123',
        ]);

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer test-token')
            && $request['reference_id'] === Order::query()->firstOrFail()->number
            && $request['items'][0]['unit_amount'] === 90000
        );
    }
}
