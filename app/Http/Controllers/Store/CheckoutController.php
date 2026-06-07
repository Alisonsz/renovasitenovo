<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\CheckoutRequest;
use App\Services\Payments\PagBankClient;
use App\Services\Store\CartService;
use App\Services\Store\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function show(Request $request, CartService $cartService, PagBankClient $pagbank): Response
    {
        $cart = $cartService->current($request);

        return Inertia::render('Store/Checkout', [
            'cart' => $cartService->payload($cart),
            'contact' => [
                'email' => $cart->email,
                'name' => $cart->customer_name,
            ],
            'pagbank' => [
                // Auto-resolved from the API token (or an explicit override).
                'public_key' => $pagbank->publicKey(),
                'env' => config('services.pagbank.env'),
                'pix_discount_percent' => (int) config('services.pagbank.pix_discount_percent', 0),
                'max_installments' => (int) config('services.pagbank.max_installments', 12),
                'interest_free_installments' => (int) config('services.pagbank.interest_free_installments', 12),
                'min_installment_cents' => (int) config('services.pagbank.min_installment_cents', 0),
            ],
        ]);
    }

    /**
     * Email-first step: persist the buyer's email on the cart as soon as they
     * begin checkout, so the cart can be recovered if they drop off. Returns
     * to the checkout page (the SPA advances to the next step).
     */
    public function identify(Request $request, CartService $cartService): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $cart = $cartService->current($request);

        if ($cart->items->isEmpty()) {
            return back()->withErrors(['cart' => 'Seu carrinho está vazio.']);
        }

        $cartService->captureContact($cart, $data['email'], $data['name'] ?? null);

        return back()->with('success', 'E-mail registrado. Continue para o pagamento.');
    }

    public function store(CheckoutRequest $request, CartService $cartService, CheckoutService $checkoutService): RedirectResponse
    {
        $cart = $cartService->current($request);
        $order = $checkoutService->createOrder($cart, $request->validated());

        // Cart is now converted; drop it from the session so the next visit
        // starts a fresh cart (prevents re-checkout of a paid cart).
        $cartService->forgetSession($request);

        if ($order->pagbank_pay_url) {
            return redirect()->away($order->pagbank_pay_url);
        }

        return redirect()->route('store.payment-return', ['order' => $order->number]);
    }
}
