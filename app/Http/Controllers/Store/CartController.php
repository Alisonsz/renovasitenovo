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
        $cartService->assertOwnership($request, $cartItem);
        $cartService->update($cartItem, $request->integer('quantity'));

        return redirect('/carrinho');
    }

    public function destroy(Request $request, CartItem $cartItem, CartService $cartService): RedirectResponse
    {
        $cartService->assertOwnership($request, $cartItem);
        $cartService->remove($cartItem);

        return redirect('/carrinho');
    }

    public function applyCoupon(Request $request, CartService $cartService): RedirectResponse
    {
        $data = $request->validate([
            'coupon' => ['required', 'string', 'max:80'],
        ]);

        $cart = $cartService->current($request);
        $cartService->applyCoupon($cart, $data['coupon']);

        return redirect('/carrinho');
    }

    public function removeCoupon(Request $request, CartService $cartService): RedirectResponse
    {
        $cart = $cartService->current($request);
        $cartService->removeCoupon($cart);

        return redirect('/carrinho');
    }
}
