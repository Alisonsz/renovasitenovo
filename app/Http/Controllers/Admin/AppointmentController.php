<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Professional;
use App\Services\Clinic\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public const STATUSES = ['scheduled', 'confirmed', 'completed', 'no_show', 'cancelled'];

    public function index(Request $request): Response
    {
        $view = $request->string('view')->toString() ?: 'day';
        $view = in_array($view, ['day', 'week'], true) ? $view : 'day';

        $anchor = $request->date('date') ?: Carbon::today();
        $anchor = Carbon::parse($anchor)->startOfDay();

        [$rangeStart, $rangeEnd] = $view === 'week'
            ? [$anchor->copy()->startOfWeek(Carbon::SUNDAY), $anchor->copy()->endOfWeek(Carbon::SATURDAY)]
            : [$anchor->copy(), $anchor->copy()->endOfDay()];

        $professionalId = $request->integer('professional_id') ?: null;

        $appointments = Appointment::query()
            ->with(['customer:id,name,phone,photo_path', 'professional:id,name,color', 'treatment:id,name'])
            ->where('starts_at', '>=', $rangeStart)
            ->where('starts_at', '<=', $rangeEnd)
            ->when($professionalId, fn ($q) => $q->where('professional_id', $professionalId))
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Appointment $a) => [
                'id' => $a->id,
                'starts_at' => $a->starts_at->toIso8601String(),
                'ends_at' => $a->ends_at->toIso8601String(),
                'date' => $a->starts_at->toDateString(),
                'time' => $a->starts_at->format('H:i'),
                'end_time' => $a->ends_at->format('H:i'),
                'duration_min' => $a->starts_at->diffInMinutes($a->ends_at),
                'status' => $a->status,
                'session_number' => $a->session_number,
                'notes' => $a->notes,
                'customer' => ['id' => $a->customer?->id, 'name' => $a->customer?->name, 'phone' => $a->customer?->phone],
                'professional' => $a->professional ? ['id' => $a->professional->id, 'name' => $a->professional->name, 'color' => $a->professional->color] : null,
                'treatment' => $a->treatment ? ['id' => $a->treatment->id, 'name' => $a->treatment->name] : null,
            ]);

        return Inertia::render('Admin/Appointments/Index', [
            'appointments' => $appointments,
            'view' => $view,
            'date' => $anchor->toDateString(),
            'rangeStart' => $rangeStart->toDateString(),
            'rangeEnd' => $rangeEnd->toDateString(),
            'professionals' => Professional::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'color']),
            'professionalId' => $professionalId,
            'statuses' => self::STATUSES,
            'hours' => ['start' => 7, 'end' => 22], // 07h–22h grid
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Appointments/Form', [
            'appointment' => null,
            'presetCustomerId' => $request->integer('customer') ?: null,
            'presetDate' => $request->date('date')?->format('Y-m-d\TH:i'),
            'customers' => $this->customerOptions(),
            'professionals' => Professional::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'color']),
            'statuses' => self::STATUSES,
        ]);
    }

    public function store(Request $request, AppointmentService $service): RedirectResponse
    {
        $service->create($this->validateData($request));

        return redirect()->route('admin.appointments.index')->with('success', 'Agendamento criado.');
    }

    public function edit(Appointment $appointment): Response
    {
        $appointment->load(['customer', 'treatment']);

        return Inertia::render('Admin/Appointments/Form', [
            'appointment' => [
                'id' => $appointment->id,
                'customer_id' => $appointment->customer_id,
                'professional_id' => $appointment->professional_id,
                'treatment_id' => $appointment->treatment_id,
                'starts_at' => $appointment->starts_at->format('Y-m-d\TH:i'),
                'duration_min' => $appointment->starts_at->diffInMinutes($appointment->ends_at),
                'status' => $appointment->status,
                'notes' => $appointment->notes,
            ],
            'customers' => $this->customerOptions(),
            'professionals' => Professional::query()->orderBy('name')->get(['id', 'name', 'color']),
            'statuses' => self::STATUSES,
            'treatments' => $appointment->customer
                ? $appointment->customer->treatments()->get(['id', 'name', 'total_sessions', 'completed_sessions'])
                : [],
        ]);
    }

    public function update(Request $request, Appointment $appointment, AppointmentService $service): RedirectResponse
    {
        $service->update($appointment, $this->validateData($request));

        return redirect()->route('admin.appointments.index')->with('success', 'Agendamento atualizado.');
    }

    public function updateStatus(Request $request, Appointment $appointment, AppointmentService $service): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(self::STATUSES)]]);
        $service->changeStatus($appointment, $data['status']);

        return back()->with('success', 'Status do agendamento atualizado.');
    }

    public function destroy(Appointment $appointment): RedirectResponse
    {
        $appointment->delete();

        return back()->with('success', 'Agendamento removido.');
    }

    /** Returns the treatments of a given customer (for the form's dynamic select). */
    public function treatmentsForCustomer(Customer $customer): JsonResponse
    {
        return response()->json(
            $customer->treatments()
                ->whereIn('status', ['active'])
                ->get(['id', 'name', 'total_sessions', 'completed_sessions', 'session_duration_min'])
        );
    }

    private function customerOptions()
    {
        return Customer::query()->orderBy('name')->get(['id', 'name', 'phone']);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'professional_id' => ['nullable', 'exists:professionals,id'],
            'treatment_id' => ['nullable', 'exists:treatments,id'],
            'starts_at' => ['required', 'date'],
            'duration_min' => ['required', 'integer', 'min:15', 'max:480'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
