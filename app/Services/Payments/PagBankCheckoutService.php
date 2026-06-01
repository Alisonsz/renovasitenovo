<?php

namespace App\Services\Payments;

use App\Models\Order;

class PagBankCheckoutService
{
    public function __construct(private readonly PagBankClient $client)
    {
    }

    public function configured(): bool
    {
        return $this->client->configured();
    }

    public function createForOrder(Order $order): Order
    {
        if (! $this->configured()) {
            return $order;
        }

        $order->load(['items', 'customer']);
        $response = $this->client->createCheckout($this->payload($order));
        $payUrl = collect($response['links'] ?? [])->firstWhere('rel', 'PAY')['href'] ?? null;

        $order->forceFill([
            'pagbank_checkout_id' => $response['id'] ?? null,
            'pagbank_pay_url' => $payUrl,
            'metadata' => array_merge($order->metadata ?? [], [
                'pagbank_checkout_response' => $response,
            ]),
        ])->save();

        $order->paymentTransactions()->create([
            'provider' => 'pagbank',
            'provider_checkout_id' => $order->pagbank_checkout_id,
            'method' => 'pagbank_checkout',
            'status' => 'checkout_created',
            'amount_cents' => $order->total_cents,
            'raw_payload' => $response,
        ]);

        return $order->refresh()->load(['items', 'customer']);
    }

    private function payload(Order $order): array
    {
        $redirectBase = rtrim((string) config('services.pagbank.redirect_base_url'), '/');

        return [
            'reference_id' => $order->number,
            'expiration_date' => now()->addDay()->toIso8601String(),
            'customer' => [
                'name' => $order->customer->name,
                'email' => $order->customer->email,
                'tax_id' => preg_replace('/\D+/', '', (string) $order->customer->document),
                'phones' => [
                    [
                        'country' => '55',
                        'area' => substr(preg_replace('/\D+/', '', (string) $order->customer->phone), 0, 2),
                        'number' => substr(preg_replace('/\D+/', '', (string) $order->customer->phone), 2),
                        'type' => 'MOBILE',
                    ],
                ],
            ],
            'items' => $order->items->map(fn ($item) => [
                'reference_id' => (string) ($item->product_id ?: $item->id),
                'name' => mb_substr($item->product_name, 0, 100),
                'quantity' => $item->quantity,
                'unit_amount' => $item->unit_price_cents,
            ])->values()->all(),
            'payment_methods' => [
                ['type' => 'PIX'],
                ['type' => 'CREDIT_CARD'],
            ],
            'payment_method_configs' => [
                [
                    'type' => 'CREDIT_CARD',
                    'config_options' => [
                        [
                            'option' => 'INSTALLMENTS_LIMIT',
                            'value' => (string) config('services.pagbank.max_installments', 12),
                        ],
                    ],
                ],
            ],
            'soft_descriptor' => config('services.pagbank.soft_descriptor'),
            'redirect_url' => $redirectBase.route('store.payment-return', ['order' => $order->number], false),
            'notification_urls' => array_values(array_filter([
                config('services.pagbank.notification_url'),
            ])),
        ];
    }
}
