<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\CheckoutRequest;
use App\Services\Store\CartService;
use App\Services\Store\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function show(Request $request, CartService $cartService): Response
    {
        $cart = $cartService->current($request);

        return Inertia::render('Store/Checkout', [
            'cart' => $cartService->payload($cart),
        ]);
    }

    public function store(CheckoutRequest $request, CartService $cartService, CheckoutService $checkoutService): RedirectResponse
    {
        $cart = $cartService->current($request);
        $order = $checkoutService->createOrder($cart, $request->validated());

        if ($order->pagbank_pay_url) {
            return redirect()->away($order->pagbank_pay_url);
        }

        return redirect()->route('store.payment-return', ['order' => $order->number]);
    }
}
