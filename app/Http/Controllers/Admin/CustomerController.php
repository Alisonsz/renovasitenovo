<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Treatment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $customers = Customer::query()
            ->withCount(['orders', 'appointments', 'treatments'])
            ->withSum('orders as orders_total_cents', 'total_cents')
            ->when($search, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('document', 'like', "%{$search}%")))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Customer $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'email' => $c->email,
                'phone' => $c->phone,
                'photo_url' => $c->photo_url,
                'orders_count' => $c->orders_count,
                'appointments_count' => $c->appointments_count,
                'treatments_count' => $c->treatments_count,
                'orders_total_cents' => (int) $c->orders_total_cents,
                'last_visit_at' => $c->last_visit_at?->format('d/m/Y'),
            ]);

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $customers,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Customers/Form', ['customer' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['photo_path'] = $this->handlePhoto($request);

        $customer = Customer::query()->create($data);

        return redirect()->route('admin.customers.show', $customer)->with('success', 'Cliente cadastrado.');
    }

    public function show(Customer $customer): Response
    {
        $customer->load([
            'orders' => fn ($q) => $q->latest(),
            'treatments' => fn ($q) => $q->with('product')->latest(),
            'appointments' => fn ($q) => $q->with('professional')->latest('starts_at'),
        ]);

        return Inertia::render('Admin/Customers/Show', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'document' => $customer->document,
                'birthdate' => $customer->birthdate?->format('d/m/Y'),
                'instagram' => $customer->instagram,
                'address' => $customer->address,
                'notes' => $customer->notes,
                'photo_url' => $customer->photo_url,
                'is_active' => $customer->is_active,
                'last_visit_at' => $customer->last_visit_at?->format('d/m/Y H:i'),
                'created_at' => $customer->created_at?->format('d/m/Y'),
                'treatments' => $customer->treatments->map(fn (Treatment $t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'product' => $t->product?->name,
                    'total_sessions' => $t->total_sessions,
                    'completed_sessions' => $t->completed_sessions,
                    'remaining' => $t->remainingSessions(),
                    'status' => $t->status,
                ])->values(),
                'appointments' => $customer->appointments->map(fn ($a) => [
                    'id' => $a->id,
                    'starts_at' => $a->starts_at?->format('d/m/Y H:i'),
                    'status' => $a->status,
                    'professional' => $a->professional?->name,
                    'session_number' => $a->session_number,
                ])->values(),
                'orders' => $customer->orders->map(fn ($o) => [
                    'id' => $o->id,
                    'number' => $o->number,
                    'payment_status' => $o->payment_status,
                    'total_cents' => $o->total_cents,
                    'created_at' => $o->created_at?->format('d/m/Y'),
                ])->values(),
            ],
            // Treatment products available to associate manually.
            'treatmentProducts' => Product::query()
                ->where('is_treatment', true)
                ->orderBy('name')
                ->get(['id', 'name', 'sessions_count', 'session_duration_min'])
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sessions_count' => $p->sessions_count ?? 1,
                    'session_duration_min' => $p->session_duration_min ?? 30,
                ]),
        ]);
    }

    public function edit(Customer $customer): Response
    {
        return Inertia::render('Admin/Customers/Form', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'document' => $customer->document,
                'birthdate' => $customer->birthdate?->format('Y-m-d'),
                'instagram' => $customer->instagram,
                'address' => $customer->address,
                'notes' => $customer->notes,
                'photo_url' => $customer->photo_url,
                'is_active' => $customer->is_active,
            ],
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $data = $this->validateData($request);

        if ($request->hasFile('photo')) {
            if ($customer->photo_path) {
                Storage::disk('public')->delete($customer->photo_path);
            }
            $data['photo_path'] = $this->handlePhoto($request);
        }

        $customer->update($data);

        return redirect()->route('admin.customers.show', $customer)->with('success', 'Cliente atualizado.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->photo_path) {
            Storage::disk('public')->delete($customer->photo_path);
        }
        $customer->delete();

        return redirect()->route('admin.customers.index')->with('success', 'Cliente removido.');
    }

    /** Manually associate a treatment package with this customer. */
    public function attachTreatment(Request $request, Customer $customer): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['nullable', 'exists:products,id'],
            'name' => ['required_without:product_id', 'nullable', 'string', 'max:255'],
            'total_sessions' => ['required', 'integer', 'min:1', 'max:100'],
            'session_duration_min' => ['nullable', 'integer', 'min:15', 'max:240'],
        ]);

        $product = ($data['product_id'] ?? null) ? Product::query()->find($data['product_id']) : null;

        $customer->treatments()->create([
            'product_id' => $product?->id,
            'name' => ($data['name'] ?? null) ?: ($product?->name ?? 'Tratamento'),
            'total_sessions' => $data['total_sessions'],
            'session_duration_min' => ($data['session_duration_min'] ?? null) ?: ($product?->session_duration_min ?? 30),
            'status' => 'active',
        ]);

        return back()->with('success', 'Tratamento associado ao cliente.');
    }

    private function validateData(Request $request): array
    {
        // Only the name is mandatory.
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'document' => ['nullable', 'string', 'max:30'],
            'birthdate' => ['nullable', 'date'],
            'instagram' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['boolean'],
        ]);
    }

    private function handlePhoto(Request $request): ?string
    {
        $request->validate(['photo' => ['nullable', 'image', 'max:5120']]); // 5MB

        if (! $request->hasFile('photo')) {
            return null;
        }

        return $request->file('photo')->store('customers', 'public');
    }
}
