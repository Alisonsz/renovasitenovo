<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'is_admin', 'created_at'])
                ->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'is_admin' => $u->is_admin,
                    'created_at' => $u->created_at?->format('d/m/Y'),
                    'is_self' => $u->id === $request->user()->id,
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'is_admin' => ['boolean'],
        ]);

        User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_admin' => (bool) ($data['is_admin'] ?? true),
            'email_verified_at' => now(),
        ]);

        return back()->with('success', 'Usuário criado.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'is_admin' => ['boolean'],
        ]);

        // Don't let an admin remove their own admin access (avoids self-lockout).
        $isAdmin = (bool) ($data['is_admin'] ?? false);
        if ($user->id === $request->user()->id && ! $isAdmin) {
            return back()->withErrors(['is_admin' => 'Você não pode remover seu próprio acesso de administrador.']);
        }

        // Don't allow demoting the last remaining admin.
        if (! $isAdmin && $user->is_admin && $this->adminCount() <= 1) {
            return back()->withErrors(['is_admin' => 'É preciso ter ao menos um administrador.']);
        }

        $user->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'is_admin' => $isAdmin,
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return back()->with('success', 'Usuário atualizado.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'Você não pode excluir a si mesmo.']);
        }

        if ($user->is_admin && $this->adminCount() <= 1) {
            return back()->withErrors(['user' => 'É preciso ter ao menos um administrador.']);
        }

        $user->delete();

        return back()->with('success', 'Usuário removido.');
    }

    private function adminCount(): int
    {
        return User::query()->where('is_admin', true)->count();
    }
}
