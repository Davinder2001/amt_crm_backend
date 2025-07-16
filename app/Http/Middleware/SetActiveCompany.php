<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\CompanyUser;
use Illuminate\Support\Facades\Auth;

class SetActiveCompany
{
    public function handle($request, Closure $next)
        {
            $user = Auth::user();

            if ($user) {
                $activeCompany = $user->companies()->wherePivot('status', 1)->first();

                if ($activeCompany) {
                    app()->instance('active_company_id', $activeCompany->id);
                }
            }

            return $next($request);
        }

}
