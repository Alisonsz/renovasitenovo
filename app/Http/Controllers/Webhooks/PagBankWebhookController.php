<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PagBankWebhookController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if (! $this->isAuthentic($request)) {
            Log::warning('PagBank webhook rejected: invalid authenticity token.', [
                'ip' => $request->ip(),
            ]);

            abort(401);
        }

        $payload = $request->all();
        $referenceId = data_get($payload, 'reference_id') ?: data_get($payload, 'reference');
        $orderId = data_get($payload, 'id');
        $checkoutId = data_get($payload, 'checkout.id') ?: data_get($payload, 'checkout_id');

        $order = Order::query()
            ->where(function ($query) use ($referenceId, $orderId, $checkoutId) {
                $query->when($referenceId, fn ($q) => $q->orWhere('number', $referenceId))
                    ->when($orderId, fn ($q) => $q->orWhere('pagbank_order_id', $orderId))
                    ->when($checkoutId, fn ($q) => $q->orWhere('pagbank_checkout_id', $checkoutId));
            })
            ->first();

        abort_unless($order, 404);

        $charge = collect(data_get($payload, 'charges', []))->first() ?: [];
        $chargeId = data_get($charge, 'id') ?: $orderId;
        $status = strtoupper((string) (data_get($charge, 'status') ?: data_get($payload, 'status') ?: 'received'));

        // Idempotency: PagBank retries deliveries. If we've already recorded this
        // exact charge+status, acknowledge without re-applying side effects.
        $alreadyHandled = $order->paymentTransactions()
            ->where('provider_transaction_id', $chargeId)
            ->where('status', $status)
            ->exists();

        if ($alreadyHandled) {
            return response()->json(null, 204);
        }

        $mapped = $this->mapStatus($status);
        $paidAmount = (int) (data_get($charge, 'amount.value') ?: 0);

        $order->paymentTransactions()->create([
            'provider' => 'pagbank',
            'provider_transaction_id' => $chargeId,
            'provider_checkout_id' => $checkoutId ?: $order->pagbank_checkout_id,
            'method' => data_get($charge, 'payment_method.type'),
            'status' => $status,
            'amount_cents' => $paidAmount ?: $order->total_cents,
            'raw_payload' => $payload,
        ]);

        // Only flip to "paid" when the captured amount matches what we expect.
        if ($mapped['payment_status'] === 'paid' && $paidAmount > 0 && $paidAmount !== (int) $order->total_cents) {
            Log::warning('PagBank webhook amount mismatch.', [
                'order' => $order->number,
                'expected' => $order->total_cents,
                'received' => $paidAmount,
            ]);

            $mapped = [
                'order_status' => 'pending',
                'payment_status' => 'review',
            ];
        }

        $order->forceFill([
            'status' => $mapped['order_status'],
            'payment_status' => $mapped['payment_status'],
            'pagbank_order_id' => $order->pagbank_order_id ?: $orderId,
            'paid_at' => $mapped['payment_status'] === 'paid' ? now() : $order->paid_at,
            'cancelled_at' => $mapped['order_status'] === 'cancelled' ? now() : $order->cancelled_at,
        ])->save();

        return response()->json(null, 204);
    }

    /**
     * Verify PagBank's x-authenticity-token header.
     * Formula (official): hex( SHA-256( token + "-" + rawBody ) ).
     */
    private function isAuthentic(Request $request): bool
    {
        $token = (string) config('services.pagbank.webhook_token')
            ?: (string) config('services.pagbank.token');

        // If no token is configured (e.g. local dev without PagBank), don't
        // hard-fail in non-production so the flow remains testable.
        if ($token === '') {
            return ! app()->isProduction();
        }

        $signature = $request->header('x-authenticity-token', '');

        if (! is_string($signature) || $signature === '') {
            return false;
        }

        $expected = hash('sha256', $token.'-'.$request->getContent());

        return hash_equals($expected, $signature);
    }

    private function mapStatus(string $status): array
    {
        return match ($status) {
            'PAID', 'COMPLETED', 'AVAILABLE' => [
                'order_status' => 'processing',
                'payment_status' => 'paid',
            ],
            // Card authorized but not captured yet — not money in the bank.
            'AUTHORIZED' => [
                'order_status' => 'pending',
                'payment_status' => 'authorized',
            ],
            'IN_ANALYSIS' => [
                'order_status' => 'pending',
                'payment_status' => 'in_analysis',
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
