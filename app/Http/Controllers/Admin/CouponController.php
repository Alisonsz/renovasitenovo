<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CouponController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Coupons/Index', [
            'coupons' => Coupon::query()
                ->latest()
                ->paginate(15)
                ->through(fn (Coupon $coupon) => [
                    'id' => $coupon->id,
                    'code' => $coupon->code,
                    'type' => $coupon->type,
                    'amount_cents' => $coupon->amount_cents,
                    'percent' => $coupon->percent,
                    'starts_at' => $coupon->starts_at?->toDateString(),
                    'expires_at' => $coupon->expires_at?->toDateString(),
                    'usage_limit' => $coupon->usage_limit,
                    'used_count' => $coupon->used_count,
                    'is_active' => $coupon->is_active,
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:80', 'unique:coupons,code'],
            'type' => ['required', 'in:fixed_cart,percent'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'expires_at' => ['nullable', 'date'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ]);

        Coupon::query()->create([
            'code' => mb_strtolower($data['code']),
            'type' => $data['type'],
            'amount_cents' => $data['type'] === 'fixed_cart' ? (int) round(((float) ($data['amount'] ?? 0)) * 100) : null,
            'percent' => $data['type'] === 'percent' ? $data['percent'] : null,
            'expires_at' => $data['expires_at'] ?? null,
            'usage_limit' => $data['usage_limit'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return redirect()->route('admin.coupons.index');
    }
}
