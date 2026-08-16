<?php

use App\Http\Middleware\EnsureEmailIsVerified;
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
    ->withMiddleware(function (Middleware $middleware) {
        // ALB の背後で動くため、X-Forwarded-For からクライアントの IP を復元する。
        // LoginRequest がログイン試行のレート制限キーに IP を使っており、
        // 信頼しないと全員が ALB の同一 IP に集約されて制限が誤爆する。
        //
        // 絶対 URL の組み立てには使わない。AppServiceProvider で APP_URL に固定している。
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'verified' => EnsureEmailIsVerified::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
