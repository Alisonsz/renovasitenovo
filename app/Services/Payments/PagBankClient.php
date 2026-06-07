<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PagBankClient
{
    public function configured(): bool
    {
        return filled(config('services.pagbank.token'));
    }

    public function hasPublicKey(): bool
    {
        return filled($this->publicKey());
    }

    /**
     * The card-encryption public key. Uses an explicitly configured key when
     * present; otherwise generates one from the API token and caches it.
     *
     * This is why the merchant only needs a single credential (the token): the
     * public key is derived from it via the /public-keys endpoint, and the
     * webhook signature is verified with the same token.
     */
    public function publicKey(): ?string
    {
        $configured = config('services.pagbank.public_key');

        if (filled($configured)) {
            return $configured;
        }

        if (! $this->configured()) {
            return null;
        }

        $cacheKey = 'pagbank:public_key:'
            .config('services.pagbank.env')
            .':'.md5((string) config('services.pagbank.token'));

        $cached = Cache::get($cacheKey);
        if (filled($cached)) {
            return $cached;
        }

        try {
            $key = $this->createPublicKey();
        } catch (\Throwable $e) {
            report($e);

            return null;
        }

        if (filled($key)) {
            Cache::put($cacheKey, $key, now()->addHours(12));
        }

        return $key;
    }

    /**
     * Generate a public key for card encryption from the API token.
     * POST /public-keys { "type": "card" } -> { "public_key": "..." }
     */
    public function createPublicKey(): ?string
    {
        $response = $this->http()
            ->post($this->baseUrl().'/public-keys', ['type' => 'card'])
            ->throw()
            ->json();

        if (filled($response['public_key'] ?? null)) {
            return $response['public_key'];
        }

        // Tolerate alternative field naming: pick any value that looks like a key.
        foreach ((array) $response as $value) {
            if (is_string($value) && str_contains($value, 'PUBLIC KEY')) {
                return $value;
            }
        }

        return null;
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
