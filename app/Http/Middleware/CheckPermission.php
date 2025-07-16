<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $permission)
    {
        $user = $request->user();

        // If there's no authenticated user
        if (! $user) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Allow if user has either 'admin' or 'super-admin' role
        if ($user->hasRole(['admin', 'super-admin'])) {
            return $next($request);
        }

        // Otherwise, check the specific permission
        if (! $user->can($permission)) {
            return response()->json(['message' => 'You do not have the required permissions.'], 403);
        }

        return $next($request);
    }
}

