<?php

namespace App\Services\Store;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartService
{
    private const SESSION_KEY = 'cart_uuid';

    public function current(Request $request): Cart
    {
        $uuid = $request->session()->get(self::SESSION_KEY);

        $cart = $uuid
            ? Cart::query()->where('uuid', $uuid)->first()
            : null;

        // Never reuse a cart that already became an order.
        if ($cart && $cart->status === 'converted') {
            $cart = null;
            $request->session()->forget(self::SESSION_KEY);
        }

        if (! $cart) {
            $cart = Cart::query()->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $request->user()?->id,
                'status' => 'active',
                'last_activity_at' => now(),
                'expires_at' => now()->addDays(14),
            ]);
            $request->session()->put(self::SESSION_KEY, $cart->uuid);
        }

        return $cart->load(['items.product.images', 'coupon']);
    }

    /**
     * Confirm a cart item belongs to the session's current cart.
     * Prevents IDOR: without this, any user could mutate any cart item by id.
     */
    public function assertOwnership(Request $request, CartItem $item): void
    {
        $uuid = $request->session()->get(self::SESSION_KEY);

        abort_if($uuid === null, 404);
        abort_unless($item->cart && $item->cart->uuid === $uuid, 404);
    }

    /**
     * Store the buyer's email (and optional name) on the cart at the very start
     * of checkout, so an abandoned cart can be recovered later.
     */
    public function captureContact(Cart $cart, string $email, ?string $name = null): Cart
    {
        $cart->forceFill(array_filter([
            'email' => mb_strtolower(trim($email)),
            'customer_name' => $name ? trim($name) : null,
            'last_activity_at' => now(),
        ], fn ($value) => $value !== null))->save();

        return $cart;
    }

    /**
     * Mark the cart as converted once an order is created from it, so it is
     * never re-checked-out and a fresh cart is started on the next visit.
     */
    public function markConverted(Cart $cart): void
    {
        $cart->forceFill([
            'status' => 'converted',
            'converted_at' => now(),
        ])->save();
    }

    public function forgetSession(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    public function add(Request $request, Product $product, int $quantity): Cart
    {
        abort_unless($product->is_active, 404);

        $cart = $this->current($request);
        $item = $cart->items()->where('product_id', $product->id)->first();

        $desired = ($item?->quantity ?? 0) + $quantity;
        $this->assertStock($product, $desired);

        if ($item) {
            $item->quantity = $desired;
            $item->unit_price_cents = $product->price_cents;
            $item->total_cents = $item->quantity * $item->unit_price_cents;
            $item->save();
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price_cents' => $product->price_cents,
                'total_cents' => $quantity * $product->price_cents,
            ]);
        }

        return $this->recalculate($cart);
    }

    public function update(CartItem $item, int $quantity): Cart
    {
        $cart = $item->cart;

        if ($quantity === 0) {
            $item->delete();

            return $this->recalculate($cart);
        }

        if ($item->product) {
            $this->assertStock($item->product, $quantity);
        }

        $item->quantity = $quantity;
        $item->total_cents = $quantity * $item->unit_price_cents;
        $item->save();

        return $this->recalculate($cart);
    }

    /**
     * Block overselling when a product manages numeric stock.
     * Products with manage_stock = false (services, custom quotes) are unlimited.
     */
    private function assertStock(Product $product, int $desiredQuantity): void
    {
        if (! $product->manage_stock) {
            return;
        }

        $available = (int) ($product->stock_quantity ?? 0);

        if ($desiredQuantity > $available) {
            throw ValidationException::withMessages([
                'quantity' => $available > 0
                    ? "Restam apenas {$available} unidade(s) de {$product->name}."
                    : "{$product->name} está sem estoque no momento.",
            ]);
        }
    }

    public function remove(CartItem $item): Cart
    {
        $cart = $item->cart;
        $item->delete();

        return $this->recalculate($cart);
    }

    public function applyCoupon(Cart $cart, string $code): Cart
    {
        $coupon = Coupon::query()
            ->where('code', mb_strtolower(trim($code)))
            ->where('is_active', true)
            ->first();

        if (! $coupon || ($coupon->expires_at && $coupon->expires_at->isPast())) {
            throw ValidationException::withMessages([
                'coupon' => 'Cupom invalido ou expirado.',
            ]);
        }

        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            throw ValidationException::withMessages([
                'coupon' => 'Este cupom atingiu o limite de uso.',
            ]);
        }

        $subtotal = (int) $cart->items()->sum('total_cents');
        $discount = $coupon->type === 'percent'
            ? (int) floor($subtotal * ((float) $coupon->percent / 100))
            : (int) $coupon->amount_cents;

        $cart->forceFill([
            'coupon_id' => $coupon->id,
            'discount_cents' => min($discount, $subtotal),
        ])->save();

        return $this->recalculate($cart);
    }

    public function removeCoupon(Cart $cart): Cart
    {
        $cart->forceFill([
            'coupon_id' => null,
            'discount_cents' => 0,
        ])->save();

        return $this->recalculate($cart);
    }

    public function recalculate(Cart $cart): Cart
    {
        $subtotal = (int) $cart->items()->sum('total_cents');
        $discount = min((int) $cart->discount_cents, $subtotal);

        $cart->forceFill([
            'subtotal_cents' => $subtotal,
            'discount_cents' => $discount,
            'total_cents' => max(0, $subtotal - $discount),
            'last_activity_at' => now(),
            // Any activity un-abandons a cart that was previously flagged.
            'status' => $cart->status === 'abandoned' ? 'active' : $cart->status,
        ])->save();

        return $cart->refresh()->load(['items.product.images', 'coupon']);
    }

    public function payload(Cart $cart): array
    {
        return [
            'id' => $cart->id,
            'uuid' => $cart->uuid,
            'subtotal_cents' => $cart->subtotal_cents,
            'discount_cents' => $cart->discount_cents,
            'total_cents' => $cart->total_cents,
            'coupon' => $cart->coupon ? [
                'code' => $cart->coupon->code,
                'type' => $cart->coupon->type,
            ] : null,
            'items' => $cart->items->map(fn (CartItem $item) => [
                'id' => $item->id,
                'quantity' => $item->quantity,
                'unit_price_cents' => $item->unit_price_cents,
                'total_cents' => $item->total_cents,
                'product' => [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'slug' => $item->product->slug,
                    'image_url' => $item->product->images->first()?->local_path ?: $item->product->images->first()?->url,
                ],
            ])->values(),
        ];
    }
}
