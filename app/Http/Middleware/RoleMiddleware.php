<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role)
    {
        // Authenticate the user
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check if the user exists and their role matches
        if (!$user || $user->role !== $role) {
            return response()->json(['error' => 'Forbidden: You do not have permission'], 403);
        }

        return $next($request);
    }
}

