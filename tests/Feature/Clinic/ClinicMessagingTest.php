<?php

namespace Tests\Feature\Clinic;

use App\Models\Appointment;
use App\Models\Customer;
use App\Services\Clinic\ClinicMessenger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ClinicMessagingTest extends TestCase
{
    use RefreshDatabase;

    private function appt(Customer $c, string $startsAt, string $status = 'scheduled'): Appointment
    {
        $start = Carbon::parse($startsAt);

        return Appointment::query()->create([
            'customer_id' => $c->id,
            'starts_at' => $start,
            'ends_at' => $start->copy()->addMinutes(30),
            'status' => $status,
        ]);
    }

    public function test_selects_appointments_due_for_reminder(): void
    {
        $messenger = app(ClinicMessenger::class);
        $now = Carbon::parse('2026-06-10 09:00:00');

        $reachable = Customer::factory()->create(['phone' => '11999998888']);
        $soon = $this->appt($reachable, '2026-06-10 18:00:00'); // within 24h
        $this->appt($reachable, '2026-06-20 18:00:00');         // too far
        $unreachable = Customer::factory()->create(['phone' => null, 'email' => null]);
        $this->appt($unreachable, '2026-06-10 19:00:00');       // no contact

        $due = $messenger->dueReminders($now);

        $this->assertCount(1, $due);
        $this->assertSame($soon->id, $due->first()->id);
    }

    public function test_reminder_not_resent(): void
    {
        $messenger = app(ClinicMessenger::class);
        $now = Carbon::parse('2026-06-10 09:00:00');
        $c = Customer::factory()->create(['phone' => '11999998888']);
        $a = $this->appt($c, '2026-06-10 18:00:00');
        $a->forceFill(['reminder_sent_at' => now()])->save();

        $this->assertCount(0, $messenger->dueReminders($now));
    }

    public function test_selects_birthdays_today(): void
    {
        $messenger = app(ClinicMessenger::class);
        $today = Carbon::parse('2026-06-10');

        $birthday = Customer::factory()->create(['birthdate' => '1990-06-10', 'phone' => '1199']);
        Customer::factory()->create(['birthdate' => '1990-06-11', 'phone' => '1199']);
        Customer::factory()->create(['birthdate' => null, 'phone' => '1199']);

        $list = $messenger->birthdaysToday($today);

        $this->assertCount(1, $list);
        $this->assertSame($birthday->id, $list->first()->id);
    }

    public function test_selects_recent_no_shows(): void
    {
        $messenger = app(ClinicMessenger::class);
        $now = Carbon::parse('2026-06-10 09:00:00');
        $c = Customer::factory()->create(['phone' => '1199']);

        $recent = $this->appt($c, '2026-06-08 10:00:00', 'no_show');
        $this->appt($c, '2026-05-01 10:00:00', 'no_show');      // too old
        $this->appt($c, '2026-06-09 10:00:00', 'completed');    // not a no-show

        $list = $messenger->noShowFollowups($now);

        $this->assertCount(1, $list);
        $this->assertSame($recent->id, $list->first()->id);
    }

    public function test_command_dry_run_runs_without_sending(): void
    {
        $c = Customer::factory()->create(['phone' => '1199', 'birthdate' => Carbon::today()->subYears(30)->format('Y-m-d')]);

        $this->artisan('clinic:send-messages --dry-run')
            ->assertExitCode(0);
    }

    public function test_messaging_disabled_blocks_real_send(): void
    {
        config()->set('clinic.messaging.enabled', false);
        $messenger = app(ClinicMessenger::class);
        $c = Customer::factory()->create(['phone' => '1199']);

        $this->assertFalse($messenger->send('birthday', $c));
    }
}
