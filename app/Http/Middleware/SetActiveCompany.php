<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\CompanyUser;
use Illuminate\Support\Facades\Auth;

class SetActiveCompany
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $activeCompanyId = CompanyUser::where('user_id', $user->id)
                ->where('status', 1)
                ->value('company_id');

            if ($activeCompanyId) {
                // Set globally in request context
                $request->attributes->set('activeCompanyId', $activeCompanyId);
            }
        }

        return $next($request);
    }
}
