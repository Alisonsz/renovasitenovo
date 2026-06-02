<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Blog\BlogController;
use App\Http\Controllers\Merchant\GoogleMerchantFeedController;
use App\Http\Controllers\Seo\LegacyUrlController;
use App\Http\Controllers\Seo\RobotsController;
use App\Http\Controllers\Seo\SitemapController;
use App\Http\Controllers\Store\CartController;
use App\Http\Controllers\Store\CartRecoveryController;
use App\Http\Controllers\Store\CategoryController;
use App\Http\Controllers\Store\CheckoutController;
use App\Http\Controllers\Store\CustomerOrdersController;
use App\Http\Controllers\Store\PaymentReturnController;
use App\Http\Controllers\Store\ProductController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Home'))->name('home');
Route::get('/sitemap.xml', [SitemapController::class, 'show'])->name('seo.sitemap');
Route::get('/robots.txt', [RobotsController::class, 'show'])->name('seo.robots');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
Route::get('/quem-somos', fn () => Inertia::render('QuemSomos'))->name('quem-somos');
Route::get('/nossa-tecnologia', fn () => Inertia::render('NossaTecnologia'))->name('nossa-tecnologia');
Route::get('/politica-de-privacidade-2', fn () => Inertia::render('Legal/Privacy'))->name('legal.privacy');
Route::get('/politica-de-reembolso', fn () => Inertia::render('Legal/Refund'))->name('legal.refund');
Route::get('/cancelamento', fn () => Inertia::render('Legal/Cancellation'))->name('legal.cancellation');
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{blogPost:slug}', [BlogController::class, 'show'])->name('blog.show.nested');
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
Route::get('/carrinho/recuperar/{token}', [CartRecoveryController::class, 'restore'])
    ->name('store.cart.recover');
Route::post('/carrinho/items', [CartController::class, 'store'])
    ->name('store.cart.items.store');
Route::patch('/carrinho/items/{cartItem}', [CartController::class, 'update'])
    ->name('store.cart.items.update');
Route::delete('/carrinho/items/{cartItem}', [CartController::class, 'destroy'])
    ->name('store.cart.items.destroy');
Route::post('/carrinho/cupom', [CartController::class, 'applyCoupon'])
    ->name('store.cart.coupon.apply');
Route::delete('/carrinho/cupom', [CartController::class, 'removeCoupon'])
    ->name('store.cart.coupon.remove');
Route::get('/checkout', [CheckoutController::class, 'show'])
    ->name('store.checkout');
Route::post('/checkout/identificacao', [CheckoutController::class, 'identify'])
    ->name('store.checkout.identify');
Route::post('/checkout', [CheckoutController::class, 'store'])
    ->name('store.checkout.store');
Route::get('/minhas-compras', [CustomerOrdersController::class, 'index'])
    ->name('store.customer-orders');
Route::get('/pedido/{order:number}/retorno', [PaymentReturnController::class, 'show'])
    ->name('store.payment-return');
Route::get('/pedido/{order:number}/status', [PaymentReturnController::class, 'status'])
    ->name('store.payment-status');
Route::get('/merchant/google.xml', [GoogleMerchantFeedController::class, 'show'])
    ->name('merchant.google');
Route::get('/{legacyPath}', [LegacyUrlController::class, 'show'])
    ->where('legacyPath', '^(?!(admin|login|logout|blog|produto|loja|carrinho|checkout|minhas-compras|pedido|merchant|webhooks|sitemap\.xml|robots\.txt)(/|$)).+')
    ->name('blog.show');
