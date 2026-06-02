<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'document' => ['required', 'string', 'max:30'],
            'payment_method' => ['required', Rule::in(['pagbank_checkout', 'pix', 'credit_card'])],

            // Card fields (transparent checkout, Phase 3). Encrypted client-side.
            'card.encrypted' => ['required_if:payment_method,credit_card', 'string'],
            'card.holder' => ['required_if:payment_method,credit_card', 'nullable', 'string', 'max:255'],
            'card.installments' => ['nullable', 'integer', 'min:1', 'max:24'],
            'card.store' => ['nullable', 'boolean'],
        ];
    }
}
