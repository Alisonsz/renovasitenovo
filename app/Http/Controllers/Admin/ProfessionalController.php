<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Professional;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfessionalController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Professionals/Index', [
            'professionals' => Professional::query()
                ->withCount('appointments')
                ->orderBy('name')
                ->get()
                ->map(fn (Professional $p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'role' => $p->role,
                    'color' => $p->color,
                    'phone' => $p->phone,
                    'email' => $p->email,
                    'is_active' => $p->is_active,
                    'appointments_count' => $p->appointments_count,
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Professional::query()->create($this->validateData($request));

        return back()->with('success', 'Profissional cadastrado.');
    }

    public function update(Request $request, Professional $professional): RedirectResponse
    {
        $professional->update($this->validateData($request));

        return back()->with('success', 'Profissional atualizado.');
    }

    public function destroy(Professional $professional): RedirectResponse
    {
        $professional->delete();

        return back()->with('success', 'Profissional removido.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:120'],
            'color' => ['nullable', 'string', 'max:9'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ]);
    }
}
