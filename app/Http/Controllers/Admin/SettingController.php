<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    private const KEYS = [
        'store_name', 'store_whatsapp', 'store_email',
        'free_shipping_threshold', 'flat_shipping_cents',
        'cart_recovery_enabled', 'cart_recovery_discount_percent', 'cart_abandon_after_minutes',
        'pix_discount_percent', 'max_installments',
    ];

    public function edit(): Response
    {
        $settings = [];
        foreach (self::KEYS as $key) {
            $settings[$key] = Setting::get($key);
        }

        // Sensible defaults pulled from config when unset.
        $settings['cart_recovery_enabled'] ??= (bool) config('cart.recovery_enabled');
        $settings['cart_recovery_discount_percent'] ??= (int) config('cart.recovery_discount_percent');
        $settings['cart_abandon_after_minutes'] ??= (int) config('cart.abandon_after_minutes');
        $settings['pix_discount_percent'] ??= (int) config('services.pagbank.pix_discount_percent');
        $settings['max_installments'] ??= (int) config('services.pagbank.max_installments');

        return Inertia::render('Admin/Settings/Edit', [
            'settings' => $settings,
            // Read-only view of which payment credentials are configured (never expose secrets).
            'pagbank' => [
                'env' => config('services.pagbank.env'),
                'token_set' => filled(config('services.pagbank.token')),
                'public_key_set' => filled(config('services.pagbank.public_key')),
                'webhook_token_set' => filled(config('services.pagbank.webhook_token')),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'store_name' => ['nullable', 'string', 'max:255'],
            'store_whatsapp' => ['nullable', 'string', 'max:40'],
            'store_email' => ['nullable', 'email', 'max:255'],
            'free_shipping_threshold' => ['nullable', 'numeric', 'min:0'],
            'flat_shipping_cents' => ['nullable', 'integer', 'min:0'],
            'cart_recovery_enabled' => ['boolean'],
            'cart_recovery_discount_percent' => ['nullable', 'integer', 'min:0', 'max:90'],
            'cart_abandon_after_minutes' => ['nullable', 'integer', 'min:5', 'max:10080'],
            'pix_discount_percent' => ['nullable', 'integer', 'min:0', 'max:90'],
            'max_installments' => ['nullable', 'integer', 'min:1', 'max:24'],
        ]);

        Setting::putMany($data);

        return back()->with('success', 'Configurações salvas.');
    }
}
