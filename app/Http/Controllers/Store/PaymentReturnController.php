<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payments\PagBankOrderService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class PaymentReturnController extends Controller
{
    public function show(Order $order): Response
    {
        $order->load(['items', 'customer']);

        return Inertia::render('Store/PaymentReturn', [
            'order' => $this->payload($order),
        ]);
    }

    /**
     * Lightweight JSON endpoint the return page polls (PIX especially) to
     * reflect payment confirmation without a manual refresh. Reconciles with
     * PagBank on each poll so we don't depend solely on the webhook.
     */
    public function status(Order $order, PagBankOrderService $pagBankOrder): JsonResponse
    {
        if (in_array($order->payment_status, ['pending', 'authorized', 'in_analysis'], true)) {
            try {
                $order = $pagBankOrder->refresh($order);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'payment_status' => $order->payment_status,
            'status' => $order->status,
            'paid' => $order->payment_status === 'paid',
        ]);
    }

    private function payload(Order $order): array
    {
        $pix = data_get($order->metadata, 'pix');

        return [
            'number' => $order->number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'total_cents' => $order->total_cents,
            'pagbank_pay_url' => $order->pagbank_pay_url,
            'pix' => $pix ? [
                'text' => $pix['text'] ?? null,
                'png' => $pix['png'] ?? null,
                'expiration_date' => $pix['expiration_date'] ?? null,
            ] : null,
            'customer' => [
                'name' => $order->customer->name,
                'email' => $order->customer->email,
            ],
            'items' => $order->items->map(fn ($item) => [
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'total_cents' => $item->total_cents,
            ])->values(),
        ];
    }
}
