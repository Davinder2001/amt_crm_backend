<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class InjectUserType
{
    public function handle(Request $request, Closure $next, $type)
    {
        $request->merge(['user_type' => $type]);
        return $next($request);
    }
}
