<?php

use App\Http\Middleware\EnsureCommittee;
use App\Http\Middleware\EnsureMember;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
        ]);

        $middleware->alias([
            'member' => EnsureMember::class,
            'committee' => EnsureCommittee::class,
        ]);

        // Razorpay posts webhooks without a CSRF token; they are checked by their signature instead.
        $middleware->validateCsrfTokens(except: [
            'webhooks/razorpay',
        ]);

        // Guests are sent to the sign-in page of the part of the app they asked for.
        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->is('admin', 'admin/*') ? route('admin.login') : route('member.login'),
        );

        // Signed-in people who open a sign-in page go to their app.
        $middleware->redirectUsersTo(
            fn (Request $request) => $request->is('admin', 'admin/*') ? route('admin.dashboard') : route('member.home'),
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
