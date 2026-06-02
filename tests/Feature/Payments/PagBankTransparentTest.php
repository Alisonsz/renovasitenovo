<?php

namespace Tests\Feature\Payments;

use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
