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

    public function createCheckout(array $payload): array
    {
        $response = $this->http()
            ->post($this->baseUrl().'/checkouts', $payload)
            ->throw();

        return $response->json();
    }

    private function http(): PendingRequest
    {
        return Http::withToken((string) config('services.pagbank.token'))
            ->acceptJson()
            ->asJson()
            ->timeout(15)
            ->retry(2, 250);
    }

    private function baseUrl(): string
    {
        return config('services.pagbank.env') === 'sandbox'
            ? 'https://sandbox.api.pagseguro.com'
            : 'https://api.pagseguro.com';
    }
}
