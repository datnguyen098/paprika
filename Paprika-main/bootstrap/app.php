<?php

use App\Http\Middleware\CheckAdmin;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetTimezone;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', [
            SetLocale::class,
            SetTimezone::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'payments/viva/webhook',
        ]);

        $middleware->alias([
            'admin' => CheckAdmin::class,
            'permission' => CheckPermission::class,
            'locale' => SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
