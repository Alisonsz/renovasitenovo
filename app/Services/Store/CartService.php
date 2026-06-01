<?php

namespace App\Services\Store;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartService
{
    private const SESSION_KEY = 'cart_uuid';

    public function current(Request $request): Cart
    {
        $uuid = $request->session()->get(self::SESSION_KEY);

        $cart = $uuid
            ? Cart::query()->where('uuid', $uuid)->first()
            : null;

        if (! $cart) {
            $cart = Cart::query()->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $request->user()?->id,
                'expires_at' => now()->addDays(14),
            ]);
            $request->session()->put(self::SESSION_KEY, $cart->uuid);
        }

        return $cart->load(['items.product.images', 'coupon']);
    }

    public function add(Request $request, Product $product, int $quantity): Cart
    {
        abort_unless($product->is_active, 404);

        $cart = $this->current($request);
        $item = $cart->items()->where('product_id', $product->id)->first();

        if ($item) {
            $item->quantity += $quantity;
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

        $item->quantity = $quantity;
        $item->total_cents = $quantity * $item->unit_price_cents;
        $item->save();

        return $this->recalculate($cart);
    }

    public function remove(CartItem $item): Cart
    {
        $cart = $item->cart;
        $item->delete();

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
