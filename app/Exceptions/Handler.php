<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Throwable;
use Illuminate\Http\JsonResponse;

class Handler extends ExceptionHandler
{
    protected $dontReport = [];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Custom unauthenticated handler.
     */
    protected function unauthenticated($request, AuthenticationException $exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(), // still uses your thrown message
            'errors' => [
                'auth' => ['Token is missing or expired.']
            ],
            'hint' => 'Please check your access token and try logging in again.',
        ], 401);
    }

    /**
     * Render other exceptions (fallback).
     */
    public function render($request, Throwable $exception): JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        return parent::render($request, $exception);
    }
}
