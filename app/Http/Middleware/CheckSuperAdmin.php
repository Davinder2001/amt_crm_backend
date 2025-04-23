<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSuperAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user || !$user->hasRole('super-admin')) {
            return response()->json(['error' => 'Unauthorized. Super Admin access required.'], 403);
        }

        return $next($request);
    }
}
