<?php

namespace App\Services\Store;

use App\Models\Cart;
use App\Models\Coupon;
use App\Mail\AbandonedCartMail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CartRecoveryService
{
    /**
     * Carts eligible for a recovery email:
     *  - status = active (not abandoned/recovered/converted)
     *  - have a captured email and at least one item
     *  - inactive longer than the abandon threshold
     *  - not older than the max age
     *  - no recovery email already sent
     */
    public function findAbandonable(int $limit): \Illuminate\Support\Collection
    {
        $now = Carbon::now();
        $threshold = $now->copy()->subMinutes((int) config('cart.abandon_after_minutes'));
        $maxAge = $now->copy()->subHours((int) config('cart.abandon_max_age_hours'));

        return Cart::query()
            ->where('status', 'active')
            ->whereNotNull('email')
            ->whereNull('recovery_email_sent_at')
            ->where('last_activity_at', '<=', $threshold)
            ->where('last_activity_at', '>=', $maxAge)
            ->where('total_cents', '>', 0)
            ->whereHas('items')
            ->orderBy('last_activity_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Mark a cart abandoned, mint a unique recovery coupon, and queue the email.
     */
    public function sendRecovery(Cart $cart): bool
    {
        if (! $cart->email || $cart->items()->count() === 0) {
            return false;
        }

        $coupon = $this->mintCoupon($cart);

        if (! $cart->recovery_token) {
            $cart->recovery_token = $this->uniqueToken();
        }

        $cart->forceFill([
            'status' => 'abandoned',
            'recovery_coupon_id' => $coupon->id,
            'recovery_token' => $cart->recovery_token,
            'recovery_email_sent_at' => now(),
        ])->save();

        Mail::to($cart->email)->queue(new AbandonedCartMail($cart->id));

        return true;
    }

    private function mintCoupon(Cart $cart): Coupon
    {
        // Reuse an existing valid recovery coupon for this cart, if present.
        if ($cart->recovery_coupon_id) {
            $existing = Coupon::query()->find($cart->recovery_coupon_id);
            if ($existing && $existing->is_active && (! $existing->expires_at || $existing->expires_at->isFuture())) {
                return $existing;
            }
        }

        $percent = (int) config('cart.recovery_discount_percent');
        $ttlHours = (int) config('cart.recovery_coupon_ttl_hours');
        $code = 'VOLTA'.$percent.'-'.mb_strtoupper(Str::random(6));

        return Coupon::query()->create([
            'code' => mb_strtolower($code),
            'type' => 'percent',
            'percent' => $percent,
            'amount_cents' => 0,
            'starts_at' => now(),
            'expires_at' => now()->addHours($ttlHours),
            'usage_limit' => 1,
            'used_count' => 0,
            'is_active' => true,
            'metadata' => [
                'source' => 'cart_recovery',
                'cart_id' => $cart->id,
            ],
        ]);
    }

    private function uniqueToken(): string
    {
        do {
            $token = Str::random(48);
        } while (Cart::query()->where('recovery_token', $token)->exists());

        return $token;
    }

    public function recoveryUrl(Cart $cart): string
    {
        return url('/carrinho/recuperar/'.$cart->recovery_token);
    }
}
