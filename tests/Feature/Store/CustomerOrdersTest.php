<?php

namespace Tests\Feature\Store;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_find_orders_by_email_and_document(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '11999999999',
            'document' => '123.456.789-09',
        ]);
        $order = Order::query()->create([
            'number' => 'RL-20260601-000001',
            'customer_id' => $customer->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'pagbank_checkout',
            'subtotal_cents' => 90000,
            'total_cents' => 90000,
        ]);
        $order->items()->create([
            'product_name' => 'Combo Virilha',
            'product_slug' => 'combo-virilha',
            'quantity' => 1,
            'unit_price_cents' => 90000,
            'total_cents' => 90000,
        ]);

        $this->get('/minhas-compras?email=maria@example.com&document=12345678909')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Store/CustomerOrders')
                ->where('searched', true)
                ->where('orders.0.number', 'RL-20260601-000001')
                ->where('orders.0.items.0.name', 'Combo Virilha')
            );
    }
}
