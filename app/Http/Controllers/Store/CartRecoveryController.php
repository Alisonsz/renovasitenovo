<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Services\Store\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartRecoveryController extends Controller
{
    public function restore(Request $request, string $token, CartService $cartService): RedirectResponse
    {
        $cart = Cart::query()
            ->where('recovery_token', $token)
            ->with('recoveryCoupon')
            ->first();

        // Invalid or already-converted token: just send them to a fresh cart.
        if (! $cart || $cart->status === 'converted') {
            return redirect('/carrinho')->with('error', 'Este link de recuperação não é mais válido.');
        }

        // Re-attach this cart to the visitor's session.
        $request->session()->put('cart_uuid', $cart->uuid);

        $cart->forceFill([
            'status' => 'recovered',
            'recovered_at' => now(),
            'last_activity_at' => now(),
        ])->save();

        // Auto-apply the recovery coupon if still valid and none applied yet.
        $coupon = $cart->recoveryCoupon;
        if ($coupon && $coupon->is_active && (! $coupon->expires_at || $coupon->expires_at->isFuture())) {
            try {
                $cartService->applyCoupon($cart, $coupon->code);

                return redirect('/carrinho')->with('success', "Cupom {$coupon->code} aplicado! Seu desconto está garantido.");
            } catch (\Throwable $e) {
                // Coupon couldn't be applied (e.g. usage limit) — still restore the cart.
                report($e);
            }
        }

        return redirect('/carrinho')->with('success', 'Seu carrinho foi recuperado!');
    }
}
