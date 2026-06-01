<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\AddCartItemRequest;
use App\Http\Requests\Store\UpdateCartItemRequest;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\Store\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function show(Request $request, CartService $cartService): Response
    {
        $cart = $cartService->current($request);

        return Inertia::render('Store/Cart', [
            'cart' => $cartService->payload($cart),
        ]);
    }

    public function store(AddCartItemRequest $request, CartService $cartService): RedirectResponse
    {
        $product = Product::query()->findOrFail($request->integer('product_id'));

        $cartService->add($request, $product, $request->integer('quantity'));

        return redirect('/carrinho');
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem, CartService $cartService): RedirectResponse
    {
        $cartService->update($cartItem, $request->integer('quantity'));

        return redirect('/carrinho');
    }

    public function destroy(CartItem $cartItem, CartService $cartService): RedirectResponse
    {
        $cartService->remove($cartItem);

        return redirect('/carrinho');
    }
}
