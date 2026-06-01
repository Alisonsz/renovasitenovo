<?php

use App\Http\Controllers\Store\CategoryController;
use App\Http\Controllers\Store\CartController;
use App\Http\Controllers\Store\ProductController;
use App\Http\Controllers\Store\CheckoutController;
use App\Http\Controllers\Store\PaymentReturnController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Merchant\GoogleMerchantFeedController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Home'))->name('home');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
Route::get('/quem-somos', fn () => Inertia::render('QuemSomos'))->name('quem-somos');
Route::get('/nossa-tecnologia', fn () => Inertia::render('NossaTecnologia'))->name('nossa-tecnologia');
Route::get('/depilacao-feminina', [CategoryController::class, 'show'])
    ->defaults('slug', 'depilacao-feminina')
    ->name('store.feminine');
Route::get('/depilacao-masculina', [CategoryController::class, 'show'])
    ->defaults('slug', 'depilacao-masculina')
    ->name('store.masculine');
Route::get('/loja/categoria/{slug}', [CategoryController::class, 'show'])
    ->name('store.category');
Route::get('/produto/{product:slug}', [ProductController::class, 'show'])
    ->name('store.product');
Route::get('/carrinho', [CartController::class, 'show'])
    ->name('store.cart');
Route::post('/carrinho/items', [CartController::class, 'store'])
    ->name('store.cart.items.store');
Route::patch('/carrinho/items/{cartItem}', [CartController::class, 'update'])
    ->name('store.cart.items.update');
Route::delete('/carrinho/items/{cartItem}', [CartController::class, 'destroy'])
    ->name('store.cart.items.destroy');
Route::get('/checkout', [CheckoutController::class, 'show'])
    ->name('store.checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])
    ->name('store.checkout.store');
Route::get('/pedido/{order:number}/retorno', [PaymentReturnController::class, 'show'])
    ->name('store.payment-return');
Route::get('/merchant/google.xml', [GoogleMerchantFeedController::class, 'show'])
    ->name('merchant.google');
