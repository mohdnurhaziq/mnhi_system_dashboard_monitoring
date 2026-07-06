<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Show a friendly, in-app page when an exception reaches the browser,
        // instead of a raw stack trace, blank screen, or cryptic status text.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            $status = $response->getStatusCode();

            // Expired CSRF token (common after a tab sits idle): bounce back with
            // a clear, actionable message rather than an error page.
            if ($status === 419) {
                return back()->with('error', 'Your session expired — please refresh and try again.');
            }

            // Friendly page for the errors a user actually hits. 500s keep the
            // detailed debug page locally so scan/analyze issues stay diagnosable.
            $friendly = in_array($status, [403, 404, 503], true)
                || ($status === 500 && ! app()->hasDebugModeEnabled());

            if ($friendly && ! $request->expectsJson()) {
                return Inertia::render('Errors/Show', ['status' => $status])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            return $response;
        });
    })->create();
