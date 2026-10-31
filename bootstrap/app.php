<?php

use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [SetLocale::class, SecurityHeaders::class, EnsureAccountActive::class]);
        // Behind the Nginx / load balancer of the deployment: trust it for the real client IP and https.
        if ($proxies = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(at: $proxies === '*' ? '*' : explode(',', $proxies));
        }
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->alias([
            'role' => EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // An expired form (idle for a while, or an old tab after logging out) is not an error page: send the
        // person back to the form with their typing kept (never the password) and a plain explanation.
        // Laravel turns TokenMismatchException into an HTTP 419 before our code sees it.
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            if ($request->routeIs('logout')) {
                return redirect()->route('home');
            }

            return redirect()->back(fallback: route('home'))
                ->withInput($request->except('password', 'password_confirmation', '_token'))
                ->withErrors(['page' => __('common.page_expired')]);
        });
    })->create();
