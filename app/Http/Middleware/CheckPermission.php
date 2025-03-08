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

        if (! $request->user()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        
        if ($request->user()->hasRole('Admin')) {
            return $next($request);
        }

        if (! $request->user()->can($permission)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return $next($request);
    }
}
