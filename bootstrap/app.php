<?php

use App\Http\Middleware\VerifyAuthToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: [
            'access_token',
        ]);

        $middleware->alias([
            'verify.auth' => VerifyAuthToken::class,
        ]);

        // Cek token sebelum route model binding, supaya request tanpa token dapat 401, bukan 404 untuk ID yang tidak ada
        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: VerifyAuthToken::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
