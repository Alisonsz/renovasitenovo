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
            // Only required for credit_card; PIX sends it as null, so it must be
            // nullable — otherwise the `string` rule rejects the whole order.
            'card' => ['nullable', 'array'],
            'card.encrypted' => ['nullable', 'required_if:payment_method,credit_card', 'string'],
            'card.holder' => ['nullable', 'required_if:payment_method,credit_card', 'string', 'max:255'],
            'card.installments' => ['nullable', 'integer', 'min:1', 'max:24'],
            'card.store' => ['nullable', 'boolean'],
        ];
    }
}
