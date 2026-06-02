<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class PagBankClient
{
    public function configured(): bool
    {
        return filled(config('services.pagbank.token'));
    }

    public function hasPublicKey(): bool
    {
        return filled(config('services.pagbank.public_key'));
    }

    public function publicKey(): ?string
    {
        return config('services.pagbank.public_key');
    }

    /**
     * Orders API — create (and optionally pay) an order.
     * Used by the transparent checkout (card charge / PIX qr_codes).
     */
    public function createOrder(array $payload): array
    {
        return $this->http()
            ->post($this->baseUrl().'/orders', $payload)
            ->throw()
            ->json();
    }

    public function getOrder(string $orderId): array
    {
        return $this->http()
            ->get($this->baseUrl().'/orders/'.$orderId)
            ->throw()
            ->json();
    }

    public function getCharge(string $chargeId): array
    {
        return $this->http()
            ->get($this->baseUrl().'/charges/'.$chargeId)
            ->throw()
            ->json();
    }

    /**
     * Refund a (paid) charge, fully or partially.
     */
    public function refundCharge(string $chargeId, ?int $amountCents = null): array
    {
        $payload = $amountCents !== null
            ? ['amount' => ['value' => $amountCents]]
            : [];

        return $this->http()
            ->post($this->baseUrl().'/charges/'.$chargeId.'/cancel', $payload)
            ->throw()
            ->json();
    }

    /**
     * Legacy hosted Checkouts API (redirect / pay-by-link). Kept as a fallback
     * payment option alongside the transparent Orders flow.
     */
    public function createCheckout(array $payload): array
    {
        return $this->http()
            ->post($this->baseUrl().'/checkouts', $payload)
            ->throw()
            ->json();
    }

    private function http(): PendingRequest
    {
        return Http::withToken((string) config('services.pagbank.token'))
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->retry(2, 250);
    }

    private function baseUrl(): string
    {
        return config('services.pagbank.env') === 'sandbox'
            ? 'https://sandbox.api.pagseguro.com'
            : 'https://api.pagseguro.com';
    }
}
