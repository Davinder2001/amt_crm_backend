<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthMiddleware
{


    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated using 'admin' guard
        if (!Auth::guard('admin')->check()) {
            return response()->json([
                'message' => 'Unauthorized access. Please log in as an admin.'
            ], 401);
        }

        // Check if the authenticated user is an admin
        $admin = Auth::guard('admin')->user();
        if ($admin->role !== 'admin') {
            return response()->json([
                'message' => 'Access denied. Only admins can perform this action.'
            ], 403);
        }

        return $next($request);
    }
}
