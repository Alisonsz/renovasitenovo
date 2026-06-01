<?php

namespace Tests\Feature\Payments;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagBankWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagbank_webhook_marks_order_as_paid(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '11999999999',
            'document' => '12345678909',
        ]);
        $order = Order::query()->create([
            'number' => 'RL-20260531-000001',
            'customer_id' => $customer->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal_cents' => 90000,
            'total_cents' => 90000,
            'pagbank_checkout_id' => 'CHEC_TEST_123',
        ]);

        $response = $this->postJson('/webhooks/pagbank', [
            'id' => 'CHEC_TEST_123',
            'reference_id' => $order->number,
            'status' => 'PAID',
            'charges' => [
                ['id' => 'CHAR_TEST_123', 'payment_method' => ['type' => 'PIX']],
            ],
        ]);

        $response->assertNoContent();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
            'payment_status' => 'paid',
        ]);
        $this->assertDatabaseHas('payment_transactions', [
            'order_id' => $order->id,
            'provider_checkout_id' => 'CHEC_TEST_123',
            'provider_transaction_id' => 'CHAR_TEST_123',
            'method' => 'PIX',
            'status' => 'PAID',
            'amount_cents' => 90000,
        ]);
    }

    public function test_pagbank_webhook_returns_not_found_for_unknown_order(): void
    {
        $response = $this->postJson('/webhooks/pagbank', [
            'reference_id' => 'RL-20260531-999999',
            'status' => 'PAID',
        ]);

        $response->assertNotFound();
    }
}
