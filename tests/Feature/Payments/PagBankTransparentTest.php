<?php

namespace Tests\Feature\Payments;

use App\Mail\OrderConfirmationMail;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Services\Payments\PagBankClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PagBankTransparentTest extends TestCase
{
    use RefreshDatabase;

    private function configurePagBank(): void
    {
        config()->set('services.pagbank.env', 'sandbox');
        config()->set('services.pagbank.token', 'test-token');
        config()->set('services.pagbank.public_key', 'PUBKEY');
        config()->set('services.pagbank.notification_url', 'https://renova.test/webhooks/pagbank');
        // Keep amounts deterministic in these tests (PIX discount covered separately).
        config()->set('services.pagbank.pix_discount_percent', 0);
    }

    private function addToCart(int $price = 90000): Product
    {
        $product = Product::factory()->create([
            'name' => 'Combo Virilha', 'price_cents' => $price,
            'regular_price_cents' => $price, 'is_active' => true,
        ]);
        $this->post('/carrinho/items', ['product_id' => $product->id, 'quantity' => 1]);

        return $product;
    }

    public function test_paid_order_queues_confirmation_email(): void
    {
        Mail::fake();
        $this->configurePagBank();
        Http::fake([
            'https://sandbox.api.pagseguro.com/orders' => Http::response([
                'id' => 'ORDE_1',
                'charges' => [['id' => 'CHAR_1', 'status' => 'PAID', 'amount' => ['value' => 90000], 'payment_method' => ['type' => 'CREDIT_CARD']]],
            ], 201),
        ]);

        $this->addToCart(90000);
        $this->post('/checkout', [
            'name' => 'Maria', 'email' => 'maria@example.com',
            'phone' => '11999999999', 'document' => '12345678909',
            'payment_method' => 'credit_card',
            'card' => ['encrypted' => 'ENC', 'holder' => 'MARIA', 'installments' => 1],
        ]);

        Mail::assertQueued(OrderConfirmationMail::class, fn ($m) => $m->hasTo('maria@example.com'));
    }

    public function test_credit_card_creates_paid_order_via_orders_api(): void
    {
        $this->configurePagBank();
        Http::fake([
            'https://sandbox.api.pagseguro.com/orders' => Http::response([
                'id' => 'ORDE_1',
                'charges' => [[
                    'id' => 'CHAR_1',
                    'status' => 'PAID',
                    'amount' => ['value' => 90000],
                    'payment_method' => ['type' => 'CREDIT_CARD', 'installments' => 3],
                ]],
            ], 201),
        ]);

        $this->addToCart(90000);

        $this->post('/checkout', [
            'name' => 'Maria Silva', 'email' => 'maria@example.com',
            'phone' => '11999999999', 'document' => '12345678909',
            'payment_method' => 'credit_card',
            'card' => ['encrypted' => 'ENC_BLOB', 'holder' => 'MARIA SILVA', 'installments' => 3],
        ])->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'pagbank_order_id' => 'ORDE_1',
            'payment_method' => 'credit_card',
            'payment_status' => 'paid',
            'status' => 'processing',
        ]);

        // Encrypted card is forwarded; raw PAN never present.
        Http::assertSent(fn ($req) => str_contains($req->url(), '/orders')
            && data_get($req->data(), 'charges.0.payment_method.card.encrypted') === 'ENC_BLOB'
            && data_get($req->data(), 'charges.0.payment_method.installments') === 3
        );
    }

    public function test_pix_creates_order_with_qr_code(): void
    {
        $this->configurePagBank();
        Http::fake([
            'https://sandbox.api.pagseguro.com/orders' => Http::response([
                'id' => 'ORDE_PIX',
                'qr_codes' => [[
                    'id' => 'QRCO_1',
                    'text' => '00020126...br.gov.bcb.pix',
                    'expiration_date' => '2026-06-01T12:00:00-03:00',
                    'links' => [['rel' => 'QRCODE.PNG', 'href' => 'https://pix.img/qr.png']],
                ]],
            ], 201),
        ]);

        $this->addToCart(50000);

        $this->post('/checkout', [
            'name' => 'Maria', 'email' => 'maria@example.com',
            'phone' => '11999999999', 'document' => '12345678909',
            'payment_method' => 'pix',
        ])->assertRedirect();

        $order = Order::query()->firstOrFail();
        $this->assertSame('ORDE_PIX', $order->pagbank_order_id);
        $this->assertSame('https://pix.img/qr.png', data_get($order->metadata, 'pix.png'));
        $this->assertSame('00020126...br.gov.bcb.pix', data_get($order->metadata, 'pix.text'));

        // Return page exposes the QR.
        $this->get("/pedido/{$order->number}/retorno")
            ->assertInertia(fn ($page) => $page
                ->component('Store/PaymentReturn')
                ->where('order.pix.png', 'https://pix.img/qr.png')
            );
    }

    public function test_pix_status_endpoint_reconciles_paid(): void
    {
        $this->configurePagBank();
        // Closure fake: POST creates the PIX order, GET reconciles as PAID.
        Http::fake(function ($request) {
            if ($request->method() === 'GET' && str_contains($request->url(), '/orders/ORDE_PIX')) {
                return Http::response([
                    'id' => 'ORDE_PIX',
                    'charges' => [['id' => 'CHAR_PIX', 'status' => 'PAID', 'amount' => ['value' => 50000]]],
                ], 200);
            }

            return Http::response([
                'id' => 'ORDE_PIX',
                'qr_codes' => [['id' => 'QRCO_1', 'text' => 'pix', 'links' => [['rel' => 'QRCODE.PNG', 'href' => 'x']]]],
            ], 201);
        });

        $this->addToCart(50000);
        $this->post('/checkout', [
            'name' => 'Maria', 'email' => 'maria@example.com',
            'phone' => '11999999999', 'document' => '12345678909',
            'payment_method' => 'pix',
        ]);

        $order = Order::query()->firstOrFail();
        $this->assertSame('pending', $order->payment_status);

        $this->getJson("/pedido/{$order->number}/status")
            ->assertOk()
            ->assertJson(['paid' => true, 'payment_status' => 'paid']);
    }

    public function test_pix_applies_configured_discount(): void
    {
        $this->configurePagBank();
        config()->set('services.pagbank.pix_discount_percent', 10);
        Http::fake([
            'https://sandbox.api.pagseguro.com/orders' => Http::response([
                'id' => 'ORDE_PIX',
                'qr_codes' => [['id' => 'Q', 'text' => 'pix', 'links' => [['rel' => 'QRCODE.PNG', 'href' => 'x']]]],
            ], 201),
        ]);

        $this->addToCart(100000); // R$1000
        $this->post('/checkout', [
            'name' => 'Maria', 'email' => 'maria@example.com',
            'phone' => '11999999999', 'document' => '12345678909',
            'payment_method' => 'pix',
        ]);

        // 10% off => total 90000, pix_discount 10000.
        $this->assertDatabaseHas('orders', [
            'payment_method' => 'pix',
            'pix_discount_cents' => 10000,
            'total_cents' => 90000,
        ]);

        // The QR charge value sent to PagBank matches the discounted total.
        Http::assertSent(fn ($req) => str_contains($req->url(), '/orders')
            && data_get($req->data(), 'qr_codes.0.amount.value') === 90000
        );
    }

    public function test_public_key_is_auto_generated_from_token_and_cached(): void
    {
        config()->set('services.pagbank.env', 'sandbox');
        config()->set('services.pagbank.token', 'test-token');
        config()->set('services.pagbank.public_key', null); // not configured -> derive from token
        Cache::flush();

        Http::fake([
            'https://sandbox.api.pagseguro.com/public-keys' => Http::response([
                'public_key' => '-----BEGIN PUBLIC KEY-----MIIBI-----END PUBLIC KEY-----',
            ], 201),
        ]);

        $client = app(PagBankClient::class);

        $this->assertStringContainsString('PUBLIC KEY', (string) $client->publicKey());

        // Second call is served from cache (no extra API hit).
        $client->publicKey();
        Http::assertSentCount(1);

        // It POSTs { type: card } with the bearer token.
        Http::assertSent(fn ($req) => str_contains($req->url(), '/public-keys')
            && $req->hasHeader('Authorization', 'Bearer test-token')
            && $req['type'] === 'card'
        );
    }

    public function test_checkout_page_exposes_auto_resolved_public_key(): void
    {
        config()->set('services.pagbank.env', 'sandbox');
        config()->set('services.pagbank.token', 'test-token');
        config()->set('services.pagbank.public_key', null);
        Cache::flush();

        Http::fake([
            'https://sandbox.api.pagseguro.com/public-keys' => Http::response(['public_key' => 'PUBKEY_AUTO'], 201),
        ]);

        $product = Product::factory()->create(['price_cents' => 90000, 'regular_price_cents' => 90000, 'is_active' => true]);
        $this->post('/carrinho/items', ['product_id' => $product->id, 'quantity' => 1]);

        $this->get('/checkout')->assertInertia(fn ($page) => $page
            ->component('Store/Checkout')
            ->where('pagbank.public_key', 'PUBKEY_AUTO')
        );
    }

    public function test_pix_checkout_succeeds_when_card_fields_are_null(): void
    {
        // Reproduces the production bug: the frontend sent card.encrypted = null on
        // PIX, and the `string` rule rejected the whole order before PagBank.
        $this->configurePagBank();
        Http::fake([
            'https://sandbox.api.pagseguro.com/orders' => Http::response([
                'id' => 'ORDE_PIX',
                'qr_codes' => [['id' => 'Q', 'text' => 'pix', 'links' => [['rel' => 'QRCODE.PNG', 'href' => 'x']]]],
            ], 201),
        ]);

        $this->addToCart(50000);

        $this->post('/checkout', [
            'name' => 'Maria', 'email' => 'maria@example.com',
            'phone' => '11999999999', 'document' => '12345678909',
            'payment_method' => 'pix',
            'card' => ['encrypted' => null, 'holder' => null, 'installments' => 1],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('orders', ['payment_method' => 'pix', 'pagbank_order_id' => 'ORDE_PIX']);
    }

    public function test_failed_payment_surfaces_error_and_keeps_cart(): void
    {
        $this->configurePagBank();
        Http::fake([
            'https://sandbox.api.pagseguro.com/orders' => Http::response(['error_messages' => [['description' => 'bad']]], 400),
        ]);

        $this->addToCart(90000);

        $response = $this->post('/checkout', [
            'name' => 'Maria', 'email' => 'maria@example.com',
            'phone' => '11999999999', 'document' => '12345678909',
            'payment_method' => 'pix',
        ]);

        // The buyer gets a visible error instead of a silent reset...
        $response->assertSessionHasErrors('payment');

        // ...the dangling order is rolled back...
        $this->assertSame(0, Order::query()->count());

        // ...and the cart survives (still active, item intact) for a retry.
        $cart = Cart::query()->latest('id')->first();
        $this->assertSame('active', $cart->status);
        $this->assertSame(1, $cart->items()->count());
    }

    public function test_localhost_notification_url_is_not_sent_to_pagbank(): void
    {
        $this->configurePagBank();
        config()->set('services.pagbank.notification_url', 'http://localhost/webhooks/pagbank');
        Http::fake([
            'https://sandbox.api.pagseguro.com/orders' => Http::response([
                'id' => 'ORDE_PIX',
                'qr_codes' => [['id' => 'Q', 'text' => 'pix', 'links' => [['rel' => 'QRCODE.PNG', 'href' => 'x']]]],
            ], 201),
        ]);

        $this->addToCart(50000);
        $this->post('/checkout', [
            'name' => 'Maria', 'email' => 'maria@example.com',
            'phone' => '11999999999', 'document' => '12345678909',
            'payment_method' => 'pix',
        ]);

        // A non-public URL must be omitted so PagBank doesn't reject the order.
        Http::assertSent(fn ($req) => str_contains($req->url(), '/orders')
            && (data_get($req->data(), 'notification_urls') === [] || data_get($req->data(), 'notification_urls') === null)
        );
    }

    public function test_hosted_checkout_still_works_as_fallback(): void
    {
        config()->set('services.pagbank.env', 'sandbox');
        config()->set('services.pagbank.token', 'test-token');
        config()->set('services.pagbank.redirect_base_url', 'https://renova.test');
        Http::fake([
            'https://sandbox.api.pagseguro.com/checkouts' => Http::response([
                'id' => 'CHEC_1',
                'links' => [['rel' => 'PAY', 'href' => 'https://pay.sandbox/redir']],
            ], 201),
        ]);

        $this->addToCart(90000);
        $this->post('/checkout', [
            'name' => 'Maria', 'email' => 'maria@example.com',
            'phone' => '11999999999', 'document' => '12345678909',
            'payment_method' => 'pagbank_checkout',
        ])->assertRedirect('https://pay.sandbox/redir');
    }
}
