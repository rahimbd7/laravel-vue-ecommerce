<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //

        $middleware->validateCsrfTokens(except: ['api/*',
        ]);

        // Never redirect API guests to a login route.
        // This prevents: "Route [login] not defined."
        $middleware->redirectGuestsTo(function (Request $request) {
            return $request->is('api/*') ? null : '/login';
        });

        $middleware->alias([
            'role'   => App\Http\Middleware\RoleMiddleware::class,
            'vendor' => App\Http\Middleware\VendorMiddleware::class,
            'guest.cart' => App\Http\Middleware\GuestCartMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                if ($e instanceof AuthenticationException) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Unauthenticated.',
                    ], 401);
                }

                if ($e instanceof AuthorizationException) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Unauthenticated.',
                    ], 401);
                }

                if ($e instanceof ModelNotFoundException) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Requested resource not found.',
                    ], 404);
                }

                if ($e instanceof HttpExceptionInterface) {
                    if ($e->getStatusCode() === 404) {
                        return response()->json([
                            'status'  => 'error',
                            'message' => 'Requested resource not found.',
                        ], 404);
                    }

                    return response()->json([
                        'status'  => 'error',
                        'message' => $e->getMessage() ?: 'An error occurred.',
                    ], $e->getStatusCode());
                }

                if ($e instanceof ValidationException) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Validation failed',
                        'errors'  => $e->errors(),
                    ], 422);
                }

                return response()->json([
                    'status'  => 'error',
                    'message' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
                ], 500);
            }
        });
    })->create();
