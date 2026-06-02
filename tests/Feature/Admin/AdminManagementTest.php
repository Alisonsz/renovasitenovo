<?php

namespace Tests\Feature\Admin;

use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function makeOrder(string $paymentStatus = 'pending'): Order
    {
        $customer = Customer::query()->create([
            'name' => 'Maria', 'email' => 'maria@example.com', 'phone' => '11999999999', 'document' => '12345678909',
        ]);

        return Order::query()->create([
            'number' => 'RL-20260602-000001',
            'customer_id' => $customer->id,
            'status' => 'pending', 'payment_status' => $paymentStatus,
            'subtotal_cents' => 90000, 'total_cents' => 90000,
        ]);
    }

    public function test_order_detail_is_viewable(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->admin())
            ->get("/admin/orders/{$order->id}")
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Admin/Orders/Show')->where('order.number', $order->number));
    }

    public function test_admin_can_update_order_status(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->admin())
            ->put("/admin/orders/{$order->id}/status", [
                'status' => 'shipped', 'payment_status' => 'paid',
            ])->assertRedirect();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'shipped', 'payment_status' => 'paid']);
        $this->assertNotNull($order->fresh()->paid_at);
    }

    public function test_refund_blocked_for_unpaid_order(): void
    {
        $order = $this->makeOrder('pending');

        $this->actingAs($this->admin())
            ->post("/admin/orders/{$order->id}/refund")
            ->assertSessionHasErrors('refund');
    }

    public function test_admin_can_crud_coupons(): void
    {
        $admin = $this->admin();

        // create
        $this->actingAs($admin)->post('/admin/coupons', [
            'code' => 'PROMO20', 'type' => 'percent', 'percent' => 20, 'is_active' => true,
        ])->assertRedirect();
        $coupon = Coupon::query()->where('code', 'promo20')->firstOrFail();

        // update
        $this->actingAs($admin)->put("/admin/coupons/{$coupon->id}", [
            'code' => 'promo20', 'type' => 'percent', 'percent' => 25, 'is_active' => false,
        ])->assertRedirect();
        $this->assertEquals('25.00', $coupon->fresh()->percent);
        $this->assertFalse($coupon->fresh()->is_active);

        // delete
        $this->actingAs($admin)->delete("/admin/coupons/{$coupon->id}")->assertRedirect();
        $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
    }

    public function test_customers_list_and_detail(): void
    {
        $this->makeOrder('paid');
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/customers')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Admin/Customers/Index'));

        $customer = Customer::query()->firstOrFail();
        $this->actingAs($admin)->get("/admin/customers/{$customer->id}")
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Admin/Customers/Show')->where('customer.email', 'maria@example.com'));
    }

    public function test_reports_render_with_real_metrics(): void
    {
        $order = $this->makeOrder('paid');
        $order->forceFill(['paid_at' => now()])->save();
        $order->items()->create(['product_name' => 'Combo', 'product_slug' => 'combo', 'quantity' => 2, 'unit_price_cents' => 45000, 'total_cents' => 90000]);

        $this->actingAs($this->admin())->get('/admin/reports')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Admin/Reports/Index')->where('summary.orders_30d', 1));
    }

    public function test_settings_can_be_saved(): void
    {
        $this->actingAs($this->admin())->put('/admin/settings', [
            'store_name' => 'Renova Laser',
            'cart_recovery_enabled' => true,
            'cart_recovery_discount_percent' => 15,
        ])->assertRedirect();

        $this->assertSame('Renova Laser', Setting::get('store_name'));
        $this->assertSame(15, Setting::get('cart_recovery_discount_percent'));
    }

    public function test_non_admin_blocked_from_all_admin_management(): void
    {
        $user = User::factory()->nonAdmin()->create();

        $this->actingAs($user)->get('/admin/orders')->assertForbidden();
        $this->actingAs($user)->get('/admin/customers')->assertForbidden();
        $this->actingAs($user)->get('/admin/reports')->assertForbidden();
        $this->actingAs($user)->get('/admin/settings')->assertForbidden();
    }
}
