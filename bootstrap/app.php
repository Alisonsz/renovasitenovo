<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        then: function (): void {
            Route::middleware('web')->group(base_path('routes/admin.php'));
            Route::middleware('web')->group(base_path('routes/webhooks.php'));
        },
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('cart:send-recovery')->everyFiveMinutes()->withoutOverlapping();
        // Clinic messaging: reminders hourly, birthdays + no-show follow-ups daily.
        $schedule->command('clinic:send-messages --type=reminder')->hourly()->withoutOverlapping();
        $schedule->command('clinic:send-messages --type=birthday')->dailyAt('09:00');
        $schedule->command('clinic:send-messages --type=no_show')->dailyAt('10:00');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
