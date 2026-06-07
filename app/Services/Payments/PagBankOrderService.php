<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Carbon;

class PagBankOrderService
{
    public function __construct(private readonly PagBankClient $client) {}

    public function configured(): bool
    {
        return $this->client->configured();
    }

    /**
     * Charge an order with a tokenized (encrypted) credit card via the Orders API.
     *
     * @param  array{encrypted:string, holder?:string, installments?:int}  $card
     */
    public function payWithCard(Order $order, array $card): Order
    {
        $order->loadMissing(['items', 'customer']);

        $installments = max(1, (int) ($card['installments'] ?? 1));

        $payload = array_merge($this->basePayload($order), [
            'charges' => [[
                'reference_id' => $order->number,
                'description' => 'Pedido '.$order->number,
                'amount' => [
                    'value' => (int) $order->total_cents,
                    'currency' => 'BRL',
                ],
                'payment_method' => [
                    'type' => 'CREDIT_CARD',
                    'installments' => $installments,
                    'capture' => true,
                    'soft_descriptor' => (string) config('services.pagbank.soft_descriptor'),
                    'card' => [
                        'encrypted' => $card['encrypted'],
                    ],
                    'holder' => [
                        'name' => $card['holder'] ?? $order->customer->name,
                        'tax_id' => $this->digits($order->customer->document),
                    ],
                ],
            ]],
        ]);

        $response = $this->client->createOrder($payload);

        return $this->applyCardResponse($order, $response, $installments);
    }

    /**
     * Create a PIX charge (QR code) for an order via the Orders API.
     */
    public function payWithPix(Order $order): Order
    {
        $order->loadMissing(['items', 'customer']);

        $minutes = max(5, (int) config('services.pagbank.pix_expiration_minutes', 30));

        $payload = array_merge($this->basePayload($order), [
            'qr_codes' => [[
                'amount' => [
                    'value' => (int) $order->total_cents,
                ],
                'expiration_date' => Carbon::now()->addMinutes($minutes)->toIso8601String(),
            ]],
        ]);

        $response = $this->client->createOrder($payload);

        return $this->applyPixResponse($order, $response);
    }

    public function refresh(Order $order): Order
    {
        if (! $order->pagbank_order_id) {
            return $order;
        }

        $response = $this->client->getOrder($order->pagbank_order_id);
        $charge = collect($response['charges'] ?? [])->first() ?: [];
        $status = strtoupper((string) ($charge['status'] ?? ''));

        if ($status !== '') {
            $this->syncStatus($order, $status, (int) ($charge['amount']['value'] ?? 0));
        }

        return $order->refresh();
    }

    private function basePayload(Order $order): array
    {
        return [
            'reference_id' => $order->number,
            'customer' => [
                'name' => $order->customer->name,
                'email' => $order->customer->email,
                'tax_id' => $this->digits($order->customer->document),
                'phones' => [$this->phone($order->customer->phone)],
            ],
            'items' => $order->items->map(fn ($item) => [
                'reference_id' => (string) ($item->product_id ?: $item->id),
                'name' => mb_substr($item->product_name, 0, 100),
                'quantity' => (int) $item->quantity,
                'unit_amount' => (int) $item->unit_price_cents,
            ])->values()->all(),
            'notification_urls' => $this->notificationUrls(),
        ];
    }

    /**
     * PagBank requires a public HTTPS notification URL. Sending a localhost or
     * http URL (e.g. when APP_URL wasn't set for production) can make the order
     * be rejected — so we only include the URL when it's actually reachable.
     * Without it the order still works; we reconcile status by polling.
     */
    private function notificationUrls(): array
    {
        $url = trim((string) config('services.pagbank.notification_url'));

        if (! str_starts_with($url, 'https://')) {
            return [];
        }

        $host = (string) (parse_url($url, PHP_URL_HOST) ?: '');

        // Reject localhost / IPs / hostnames without a public domain.
        if ($host === '' || ! str_contains($host, '.') || in_array($host, ['localhost', '127.0.0.1'], true)) {
            return [];
        }

        return [$url];
    }

    private function applyCardResponse(Order $order, array $response, int $installments): Order
    {
        $charge = collect($response['charges'] ?? [])->first() ?: [];
        $status = strtoupper((string) ($charge['status'] ?? 'PENDING'));
        $mapped = $this->mapStatus($status);

        $order->forceFill([
            'pagbank_order_id' => $response['id'] ?? $order->pagbank_order_id,
            'status' => $mapped['order_status'],
            'payment_status' => $mapped['payment_status'],
            'paid_at' => $mapped['payment_status'] === 'paid' ? now() : $order->paid_at,
            'metadata' => array_merge($order->metadata ?? [], [
                'installments' => $installments,
                'pagbank_order_response' => $response,
            ]),
        ])->save();

        $order->paymentTransactions()->create([
            'provider' => 'pagbank',
            'provider_transaction_id' => $charge['id'] ?? null,
            'provider_checkout_id' => $response['id'] ?? null,
            'method' => 'CREDIT_CARD',
            'status' => $status,
            'amount_cents' => (int) ($charge['amount']['value'] ?? $order->total_cents),
            'raw_payload' => $response,
        ]);

        return $order->refresh();
    }

    private function applyPixResponse(Order $order, array $response): Order
    {
        $qr = collect($response['qr_codes'] ?? [])->first() ?: [];
        $pngLink = collect($qr['links'] ?? [])->firstWhere('rel', 'QRCODE.PNG')['href'] ?? null;

        $order->forceFill([
            'pagbank_order_id' => $response['id'] ?? $order->pagbank_order_id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'metadata' => array_merge($order->metadata ?? [], [
                'pix' => [
                    'qr_id' => $qr['id'] ?? null,
                    'text' => $qr['text'] ?? null,
                    'png' => $pngLink,
                    'expiration_date' => $qr['expiration_date'] ?? null,
                ],
                'pagbank_order_response' => $response,
            ]),
        ])->save();

        $order->paymentTransactions()->create([
            'provider' => 'pagbank',
            'provider_checkout_id' => $response['id'] ?? null,
            'method' => 'PIX',
            'status' => 'WAITING',
            'amount_cents' => (int) $order->total_cents,
            'raw_payload' => $response,
        ]);

        return $order->refresh();
    }

    private function syncStatus(Order $order, string $status, int $paidAmount): void
    {
        $mapped = $this->mapStatus($status);

        if ($mapped['payment_status'] === 'paid' && $paidAmount > 0 && $paidAmount !== (int) $order->total_cents) {
            return; // amount mismatch — leave for manual review / webhook
        }

        $order->forceFill([
            'status' => $mapped['order_status'],
            'payment_status' => $mapped['payment_status'],
            'paid_at' => $mapped['payment_status'] === 'paid' ? ($order->paid_at ?? now()) : $order->paid_at,
            'cancelled_at' => $mapped['order_status'] === 'cancelled' ? ($order->cancelled_at ?? now()) : $order->cancelled_at,
        ])->save();
    }

    private function mapStatus(string $status): array
    {
        return match ($status) {
            'PAID', 'COMPLETED', 'AVAILABLE' => ['order_status' => 'processing', 'payment_status' => 'paid'],
            'AUTHORIZED' => ['order_status' => 'pending', 'payment_status' => 'authorized'],
            'IN_ANALYSIS' => ['order_status' => 'pending', 'payment_status' => 'in_analysis'],
            'WAITING' => ['order_status' => 'pending', 'payment_status' => 'pending'],
            'CANCELED', 'CANCELLED' => ['order_status' => 'cancelled', 'payment_status' => 'cancelled'],
            'DECLINED' => ['order_status' => 'pending', 'payment_status' => 'declined'],
            default => ['order_status' => 'pending', 'payment_status' => 'pending'],
        };
    }

    private function digits(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    private function phone(?string $value): array
    {
        $digits = $this->digits($value);

        return [
            'country' => '55',
            'area' => substr($digits, 0, 2),
            'number' => substr($digits, 2),
            'type' => 'MOBILE',
        ];
    }
}
