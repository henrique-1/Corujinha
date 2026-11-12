<?php

use App\Http\Middleware\EnsureUserRole;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', '127.0.0.1'));

        $middleware->trustHosts(
            at: fn () => array_values(array_filter([
                parse_url((string) config('app.url'), PHP_URL_HOST),
                'localhost',
                '127.0.0.1',
            ])),
            subdomains: false
        );

        $middleware->web(append: [
            SecurityHeaders::class,
        ]);

        $middleware->redirectUsersTo(function (Request $request) {
            $user = $request->user();
            if ($user?->isSuperAdmin()) {
                return route('admin.professores.index');
            }

            return route('professor.salas.index');
        });

        $middleware->redirectGuestsTo(fn () => route('login'));

        $middleware->alias([
            'role' => EnsureUserRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
