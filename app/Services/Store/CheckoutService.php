<?php

namespace App\Services\Store;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\Payments\PagBankCheckoutService;
use App\Services\Payments\PagBankOrderService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly PagBankCheckoutService $pagBankCheckout,
        private readonly PagBankOrderService $pagBankOrder,
        private readonly CartService $cartService,
    ) {}

    public function createOrder(Cart $cart, array $data): Order
    {
        $cart->load(['items.product.images', 'coupon']);

        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Seu carrinho está vazio.',
            ]);
        }

        // Idempotency guard: a converted cart already produced an order.
        if ($cart->status === 'converted') {
            throw ValidationException::withMessages([
                'cart' => 'Este carrinho já foi finalizado.',
            ]);
        }

        $pixDiscountCents = $this->pixDiscountFor($cart, $data['payment_method']);

        $order = DB::transaction(function () use ($cart, $data, $pixDiscountCents) {
            // Lock managed-stock products and validate availability before committing.
            $this->reserveStock($cart);

            $customer = Customer::query()->updateOrCreate(
                ['email' => mb_strtolower(trim($data['email']))],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'document' => $data['document'],
                ]
            );

            $total = max(0, $cart->total_cents - $pixDiscountCents);

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
                'pix_discount_cents' => $pixDiscountCents,
                'total_cents' => $total,
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

        // Attempt payment OUTSIDE the creation transaction. The cart is only
        // converted (and the coupon burned) once payment has been initiated, so
        // a failed payment leaves the cart intact for a retry instead of
        // stranding the buyer with an empty cart.
        try {
            $order = $this->processPayment($order, $data);
        } catch (\Throwable $e) {
            $this->rollbackOrder($order, $cart);

            throw $e;
        }

        DB::transaction(function () use ($cart) {
            if ($cart->coupon) {
                $cart->coupon->increment('used_count');
            }

            // Mark the cart converted so a double-submit can't reuse it.
            $this->cartService->markConverted($cart);
        });

        return $order;
    }

    /**
     * Undo a created-but-unpaid order so the buyer can retry: restore reserved
     * stock and remove the dangling order. The cart stays active.
     */
    private function rollbackOrder(Order $order, Cart $cart): void
    {
        DB::transaction(function () use ($order, $cart) {
            foreach ($cart->items as $item) {
                if ($item->product && $item->product->manage_stock) {
                    Product::query()
                        ->whereKey($item->product_id)
                        ->lockForUpdate()
                        ->first()
                        ?->increment('stock_quantity', $item->quantity);
                }
            }

            $order->items()->delete();
            $order->paymentTransactions()->delete();
            $order->delete();
        });
    }

    /**
     * Route the freshly created order to the right PagBank flow based on the
     * chosen payment method. Transparent (Orders API) for pix/credit_card;
     * hosted checkout (redirect) as a fallback option.
     */
    private function processPayment(Order $order, array $data): Order
    {
        $method = $data['payment_method'];

        // Without a configured token we can't call PagBank — return the order
        // as-is so the return page can show a pending state (dev-friendly).
        if (! $this->pagBankOrder->configured()) {
            return $order;
        }

        try {
            return match ($method) {
                'pix' => $this->pagBankOrder->payWithPix($order),
                'credit_card' => $this->pagBankOrder->payWithCard($order, [
                    'encrypted' => data_get($data, 'card.encrypted'),
                    'holder' => data_get($data, 'card.holder'),
                    'installments' => (int) data_get($data, 'card.installments', 1),
                ]),
                default => $this->pagBankCheckout->createForOrder($order),
            };
        } catch (RequestException $e) {
            // Surface a friendly error; the order stays pending and can be retried.
            report($e);

            throw ValidationException::withMessages([
                'payment' => 'Não foi possível processar o pagamento. Verifique os dados e tente novamente.',
            ]);
        }
    }

    private function pixDiscountFor(Cart $cart, string $paymentMethod): int
    {
        if (! in_array($paymentMethod, ['pix', 'pagbank_pix'], true)) {
            return 0;
        }

        $percent = (int) config('services.pagbank.pix_discount_percent', 0);

        if ($percent <= 0) {
            return 0;
        }

        return (int) floor($cart->total_cents * ($percent / 100));
    }

    private function reserveStock(Cart $cart): void
    {
        foreach ($cart->items as $item) {
            $product = Product::query()
                ->whereKey($item->product_id)
                ->lockForUpdate()
                ->first();

            if (! $product || ! $product->manage_stock) {
                continue;
            }

            $available = (int) ($product->stock_quantity ?? 0);

            if ($item->quantity > $available) {
                throw ValidationException::withMessages([
                    'cart' => "{$product->name}: estoque insuficiente (restam {$available}).",
                ]);
            }

            $product->decrement('stock_quantity', $item->quantity);
        }
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
