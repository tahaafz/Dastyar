<?php

use App\Http\Middleware\AlwaysOkWebhook;
use App\Http\Middleware\VerifyTelegramIp;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/v1/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->group('telegram', [VerifyTelegramIp::class, AlwaysOkWebhook::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
