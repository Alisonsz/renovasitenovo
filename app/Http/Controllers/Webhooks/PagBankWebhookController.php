<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PagBankWebhookController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $payload = $request->all();
        $referenceId = data_get($payload, 'reference_id') ?: data_get($payload, 'reference');
        $checkoutId = data_get($payload, 'id') ?: data_get($payload, 'checkout.id');

        $order = Order::query()
            ->when($referenceId, fn ($query) => $query->orWhere('number', $referenceId))
            ->when($checkoutId, fn ($query) => $query->orWhere('pagbank_checkout_id', $checkoutId))
            ->first();

        abort_unless($order, 404);

        $charge = collect(data_get($payload, 'charges', []))->first() ?: [];
        $status = strtoupper((string) (data_get($charge, 'status') ?: data_get($payload, 'status') ?: 'received'));
        $mapped = $this->mapStatus($status);

        $order->paymentTransactions()->create([
            'provider' => 'pagbank',
            'provider_transaction_id' => data_get($charge, 'id'),
            'provider_checkout_id' => $checkoutId ?: $order->pagbank_checkout_id,
            'method' => data_get($charge, 'payment_method.type'),
            'status' => $status,
            'amount_cents' => $order->total_cents,
            'raw_payload' => $payload,
        ]);

        $order->forceFill([
            'status' => $mapped['order_status'],
            'payment_status' => $mapped['payment_status'],
            'paid_at' => $mapped['payment_status'] === 'paid' ? now() : $order->paid_at,
            'cancelled_at' => $mapped['order_status'] === 'cancelled' ? now() : $order->cancelled_at,
        ])->save();

        return response()->json(null, 204);
    }

    private function mapStatus(string $status): array
    {
        return match ($status) {
            'PAID', 'APPROVED', 'COMPLETED', 'AUTHORIZED' => [
                'order_status' => 'processing',
                'payment_status' => 'paid',
            ],
            'CANCELLED', 'CANCELED' => [
                'order_status' => 'cancelled',
                'payment_status' => 'cancelled',
            ],
            'DECLINED', 'FAILED' => [
                'order_status' => 'pending',
                'payment_status' => 'failed',
            ],
            default => [
                'order_status' => 'pending',
                'payment_status' => 'pending',
            ],
        };
    }
}
