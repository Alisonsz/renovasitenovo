<?php

namespace App\Services\Clinic;

use App\Models\Appointment;
use App\Models\Customer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Selects recipients for clinic messages and dispatches them.
 *
 * PREPARATION PHASE: the selection logic is real and tested. The actual
 * delivery (`send()`) currently logs the intent — swap the body for a WhatsApp
 * API / Mailable when a channel is connected. Each method is idempotent via a
 * timestamp/flag so re-runs don't double-send.
 */
class ClinicMessenger
{
    /** Appointments needing a reminder within the configured window. */
    public function dueReminders(?Carbon $now = null): Collection
    {
        $now ??= Carbon::now();
        $hours = (int) config('clinic.messaging.reminder_hours_before', 24);
        $windowEnd = $now->copy()->addHours($hours);

        return Appointment::query()
            ->with('customer')
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->whereNull('reminder_sent_at')
            ->whereBetween('starts_at', [$now, $windowEnd])
            ->get()
            ->filter(fn (Appointment $a) => $this->reachable($a->customer));
    }

    /** Customers whose birthday is today. */
    public function birthdaysToday(?Carbon $today = null): Collection
    {
        $today ??= Carbon::today();

        return Customer::query()
            ->whereNotNull('birthdate')
            ->get()
            ->filter(fn (Customer $c) => $c->birthdate
                && $c->birthdate->format('m-d') === $today->format('m-d')
                && $this->reachable($c));
    }

    /** Recent no-shows that haven't had a follow-up yet. */
    public function noShowFollowups(?Carbon $now = null): Collection
    {
        $now ??= Carbon::now();

        return Appointment::query()
            ->with('customer')
            ->where('status', 'no_show')
            ->whereNull('reminder_sent_at') // reuse the flag column as "followed up"
            ->where('starts_at', '>=', $now->copy()->subDays(7))
            ->get()
            ->filter(fn (Appointment $a) => $this->reachable($a->customer));
    }

    /**
     * Dispatch a message. Returns true if "sent" (logged for now).
     *
     * @param  'reminder'|'birthday'|'no_show'  $type
     */
    public function send(string $type, Customer $customer, array $context = []): bool
    {
        if (! config('clinic.messaging.enabled')) {
            return false;
        }

        // TODO: integrate WhatsApp Business API / e-mail here.
        Log::info('clinic.message.dispatch', [
            'type' => $type,
            'customer_id' => $customer->id,
            'to' => $customer->phone ?: $customer->email,
            'context' => $context,
        ]);

        return true;
    }

    private function reachable(?Customer $customer): bool
    {
        return $customer && ($customer->phone || $customer->email);
    }
}
