<?php

namespace Tests\Feature\Admin;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Professional;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_calendar_renders(): void
    {
        $this->actingAs($this->admin())->get('/admin/appointments?view=week')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Admin/Appointments/Index')->where('view', 'week'));
    }

    public function test_can_create_appointment(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->admin())->post('/admin/appointments', [
            'customer_id' => $customer->id,
            'starts_at' => '2026-06-10T09:00',
            'duration_min' => 30,
        ])->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'customer_id' => $customer->id,
            'status' => 'scheduled',
        ]);
    }

    public function test_double_booking_same_professional_is_blocked(): void
    {
        $customer = Customer::factory()->create();
        $other = Customer::factory()->create();
        $prof = Professional::query()->create(['name' => 'Ana', 'color' => '#000']);
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/appointments', [
            'customer_id' => $customer->id, 'professional_id' => $prof->id,
            'starts_at' => '2026-06-10T09:00', 'duration_min' => 30,
        ])->assertRedirect();

        // Overlaps 09:00–09:30 → should fail.
        $this->actingAs($admin)->post('/admin/appointments', [
            'customer_id' => $other->id, 'professional_id' => $prof->id,
            'starts_at' => '2026-06-10T09:15', 'duration_min' => 30,
        ])->assertSessionHasErrors('starts_at');

        $this->assertSame(1, Appointment::query()->count());
    }

    public function test_no_conflict_without_professional(): void
    {
        $c1 = Customer::factory()->create();
        $c2 = Customer::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/appointments', [
            'customer_id' => $c1->id, 'starts_at' => '2026-06-10T09:00', 'duration_min' => 30,
        ])->assertRedirect();
        // same slot, no professional → allowed (two rooms / generic agenda)
        $this->actingAs($admin)->post('/admin/appointments', [
            'customer_id' => $c2->id, 'starts_at' => '2026-06-10T09:00', 'duration_min' => 30,
        ])->assertRedirect();

        $this->assertSame(2, Appointment::query()->count());
    }

    public function test_completing_session_advances_treatment(): void
    {
        $customer = Customer::factory()->create();
        $treatment = $customer->treatments()->create([
            'name' => '10 sessões', 'total_sessions' => 10, 'completed_sessions' => 0, 'status' => 'active',
        ]);
        $appt = Appointment::query()->create([
            'customer_id' => $customer->id, 'treatment_id' => $treatment->id,
            'starts_at' => '2026-06-10 09:00:00', 'ends_at' => '2026-06-10 09:30:00', 'status' => 'scheduled',
        ]);

        $this->actingAs($this->admin())
            ->put("/admin/appointments/{$appt->id}/status", ['status' => 'completed'])
            ->assertRedirect();

        $this->assertSame(1, $treatment->fresh()->completed_sessions);
        $this->assertNotNull($customer->fresh()->last_visit_at);
    }

    public function test_paid_order_auto_provisions_treatment(): void
    {
        $product = Product::factory()->create([
            'name' => '10 Sessões Virilha', 'is_treatment' => true,
            'sessions_count' => 10, 'session_duration_min' => 30,
            'price_cents' => 90000, 'is_active' => true,
        ]);
        $customer = Customer::factory()->create();
        $order = Order::query()->create([
            'number' => 'RL-T-1', 'customer_id' => $customer->id,
            'status' => 'pending', 'payment_status' => 'pending',
            'subtotal_cents' => 90000, 'total_cents' => 90000,
        ]);
        $order->items()->create([
            'product_id' => $product->id, 'product_name' => $product->name, 'product_slug' => 'x',
            'quantity' => 1, 'unit_price_cents' => 90000, 'total_cents' => 90000,
        ]);

        // Transition to paid → triggers provisioning via Order::updated.
        $order->update(['payment_status' => 'paid']);

        $this->assertDatabaseHas('treatments', [
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'total_sessions' => 10,
        ]);

        // Idempotent: firing paid again must not duplicate.
        $order->update(['payment_status' => 'paid']);
        $this->assertSame(1, Treatment::query()->where('order_id', $order->id)->count());
    }

    public function test_non_admin_blocked_from_agenda(): void
    {
        $user = User::factory()->nonAdmin()->create();
        $this->actingAs($user)->get('/admin/appointments')->assertForbidden();
    }
}
