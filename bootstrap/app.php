<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function ($middleware) {
        $middleware->alias([
            'jwt.auth' => \App\Http\Middleware\JwtAuth::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })

    ->withProviders([
        App\Providers\BroadcastServiceProvider::class,
    ])->create();
