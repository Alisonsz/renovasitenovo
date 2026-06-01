<?php

use App\Http\Controllers\Webhooks\PagBankWebhookController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::prefix('webhooks')
    ->name('webhooks.')
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->group(function () {
        Route::post('/pagbank', [PagBankWebhookController::class, 'store'])->name('pagbank');
    });
