<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\RoleMiddleware;
use App\Exceptions\BusinessException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 1. Business Logic Error (Custom)
        $exceptions->render(function (BusinessException $e) {
            return response()->json([
                'success' => false, // status: fail
                'message' => $e->getMessage(),
                'errors'  => null,
            ], 422); // Unprocessable Entity
        });

        // 2. Not Found Error (404) - e.g. ModelNotFound or Invalid Route
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found or invalid endpoint.',
                'errors'  => null,
            ], 404);
        });

        // 3. Authentication Error (401)
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please login.',
                'errors'  => null,
            ], 401);
        });

        // 4. Access Denied (403)
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.',
                'errors'  => null,
            ], 403);
        });

        // 5. Validation Error (422)
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        });
    })
    ->create();
