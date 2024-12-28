<?php

use App\Http\Middleware\Otsglobal;
use App\Http\Middleware\SwitchTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            Otsglobal::class,
            SwitchTenant::class,
        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'sanctum/csrf-cookie',
            'api/*',
            'login',

        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('app:backup-tenants')->weekly();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
