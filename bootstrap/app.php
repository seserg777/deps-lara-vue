<?php

use App\Exceptions\CatalogApiException;
use App\Http\Middleware\DenySwaggerInProduction;
use App\Http\Responses\ApiJson;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'swagger.non_production' => DenySwaggerInProduction::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/catalog*')) {
                return null;
            }

            return ApiJson::error(
                $e->getMessage(),
                422,
                'validation_failed',
                $e->errors(),
            );
        });

        $exceptions->render(function (CatalogApiException $e, Request $request) {
            if (! $request->is('api/catalog*')) {
                return null;
            }

            $code = $e->code_api !== null
                ? (string) $e->code_api
                : ($e->status ?? 'catalog_api_error');

            return ApiJson::error($e->getMessage(), 502, $code);
        });

        $exceptions->render(function (HttpException $e, Request $request) {
            if (! $request->is('api/catalog*')) {
                return null;
            }

            $status = $e->getStatusCode();
            $message = $e->getMessage();
            if ($message === '' && $status === 404) {
                $message = 'Not found.';
            }
            if ($message === '') {
                $message = 'HTTP error';
            }

            $code = match ($status) {
                404 => 'not_found',
                403 => 'forbidden',
                401 => 'unauthorized',
                419 => 'page_expired',
                429 => 'too_many_requests',
                503 => 'service_unavailable',
                default => 'http_error',
            };

            return ApiJson::error($message, $status, $code);
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/catalog*')) {
                return null;
            }

            if ($e instanceof ValidationException || $e instanceof CatalogApiException || $e instanceof HttpException) {
                return null;
            }

            $message = config('app.debug') ? $e->getMessage() : 'Server Error';

            return ApiJson::error($message, 500, 'server_error');
        });
    })->create();
