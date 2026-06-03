<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Admin/Profile/Edit', [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /** Update name + e-mail (requires current password to change e-mail). */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['required', 'current_password'],
        ], [
            'current_password.current_password' => 'A senha atual está incorreta.',
        ]);

        $user->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
        ])->save();

        return back()->with('success', 'Dados da conta atualizados.');
    }

    /** Change password (requires current password). */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.current_password' => 'A senha atual está incorreta.',
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($request->input('password')),
        ])->save();

        return back()->with('success', 'Senha alterada com sucesso.');
    }
}
