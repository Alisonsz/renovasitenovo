<?php

namespace App\Services\Clinic;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Treatment;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    /**
     * Create an appointment, guarding against double-booking the same
     * professional in an overlapping time slot.
     */
    public function create(array $data): Appointment
    {
        $start = Carbon::parse($data['starts_at']);
        $duration = (int) ($data['duration_min'] ?? 30);
        $end = (clone $start)->addMinutes($duration);

        $this->assertNoConflict($data['professional_id'] ?? null, $start, $end);

        $sessionNumber = null;
        if (! empty($data['treatment_id'])) {
            $treatment = Treatment::query()->find($data['treatment_id']);
            $sessionNumber = $treatment ? $treatment->completed_sessions + 1 : null;
        }

        return Appointment::query()->create([
            'customer_id' => $data['customer_id'],
            'professional_id' => $data['professional_id'] ?? null,
            'treatment_id' => $data['treatment_id'] ?? null,
            'starts_at' => $start,
            'ends_at' => $end,
            'session_number' => $sessionNumber,
            'status' => $data['status'] ?? 'scheduled',
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function update(Appointment $appointment, array $data): Appointment
    {
        $start = Carbon::parse($data['starts_at']);
        $duration = (int) ($data['duration_min'] ?? $appointment->starts_at->diffInMinutes($appointment->ends_at));
        $end = (clone $start)->addMinutes($duration);

        $this->assertNoConflict($data['professional_id'] ?? null, $start, $end, $appointment->id);

        $appointment->update([
            'customer_id' => $data['customer_id'],
            'professional_id' => $data['professional_id'] ?? null,
            'treatment_id' => $data['treatment_id'] ?? null,
            'starts_at' => $start,
            'ends_at' => $end,
            'notes' => $data['notes'] ?? null,
        ]);

        return $appointment;
    }

    /**
     * Change status. When marking completed, advance the linked treatment and
     * stamp the customer's last visit. When un-completing, roll it back.
     */
    public function changeStatus(Appointment $appointment, string $status): Appointment
    {
        $was = $appointment->status;

        $appointment->forceFill([
            'status' => $status,
            'completed_at' => $status === 'completed' ? now() : null,
            'confirmed_at' => $status === 'confirmed' ? ($appointment->confirmed_at ?? now()) : $appointment->confirmed_at,
        ])->save();

        if ($status === 'completed' && $was !== 'completed') {
            Customer::query()->whereKey($appointment->customer_id)
                ->update(['last_visit_at' => $appointment->starts_at]);
        }

        // Recompute treatment progress from its completed appointments.
        if ($appointment->treatment_id) {
            $appointment->treatment?->syncProgress();
        }

        return $appointment;
    }

    private function assertNoConflict(?int $professionalId, Carbon $start, Carbon $end, ?int $ignoreId = null): void
    {
        if (! $professionalId) {
            return; // no professional assigned → no exclusivity to enforce
        }

        $conflict = Appointment::query()
            ->active()
            ->where('professional_id', $professionalId)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->between($start, $end)
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'starts_at' => 'Já existe um agendamento para este profissional neste horário.',
            ]);
        }
    }
}
