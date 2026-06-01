<?php

namespace App\Services\Store;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\Order;
use App\Services\Payments\PagBankCheckoutService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(private readonly PagBankCheckoutService $pagBankCheckout)
    {
    }

    public function createOrder(Cart $cart, array $data): Order
    {
        $cart->load(['items.product.images', 'coupon']);

        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Seu carrinho está vazio.',
            ]);
        }

        $order = DB::transaction(function () use ($cart, $data) {
            $customer = Customer::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'document' => $data['document'],
                ]
            );

            $order = Order::query()->create([
                'number' => $this->nextOrderNumber(),
                'user_id' => $cart->user_id,
                'customer_id' => $customer->id,
                'cart_id' => $cart->id,
                'coupon_id' => $cart->coupon_id,
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $data['payment_method'],
                'subtotal_cents' => $cart->subtotal_cents,
                'discount_cents' => $cart->discount_cents,
                'pix_discount_cents' => 0,
                'total_cents' => $cart->total_cents,
                'currency' => 'BRL',
            ]);

            foreach ($cart->items as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'product_slug' => $item->product->slug,
                    'quantity' => $item->quantity,
                    'unit_price_cents' => $item->unit_price_cents,
                    'total_cents' => $item->total_cents,
                    'metadata' => [
                        'image_url' => $item->product->images->first()?->local_path ?: $item->product->images->first()?->url,
                    ],
                ]);
            }

            $order->paymentTransactions()->create([
                'provider' => 'pagbank',
                'method' => $data['payment_method'],
                'status' => 'pending',
                'amount_cents' => $order->total_cents,
                'raw_payload' => ['source' => 'local_checkout_pending'],
            ]);

            return $order->load(['items', 'customer']);
        });

        return $this->pagBankCheckout->createForOrder($order);
    }

    private function nextOrderNumber(): string
    {
        $prefix = 'RL-'.now()->format('Ymd').'-';
        $last = Order::query()
            ->where('number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('number');

        $sequence = $last ? ((int) substr($last, -6)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }
}
