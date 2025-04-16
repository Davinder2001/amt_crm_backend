<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;

class Authenticate extends Middleware
{
    /**
     * Customize the redirect path (null for API).
     */
    protected function redirectTo($request)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthorized00000'], 401);
        }

        return null;
    }
    

    /**
     * Customize the unauthenticated response.
     */
    protected function unauthenticated($request, array $guards)
    {
        throw new AuthenticationException(
            'You are not logged in. Please authenticate.',
            $guards,
            $this->redirectTo($request)
        );
    }

    
}
