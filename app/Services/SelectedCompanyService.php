<?php

namespace App\Services;

use App\Models\CompanyUser;
use Illuminate\Support\Facades\Auth;

class SelectedCompanyService
{
    public static function getSelectedCompanyOrFail()
    {
        if (!Auth::check()) {
            abort(response()->json(['error' => 'Unauthorized'], 401));
        }

        $user = Auth::user();

        if ($user->hasRole('super-admin')) {
            return (object) [
                'super_admin' => true,
                'company_id' => null,
                'role' => 'super-admin',
                'selected_company' => 'super-admin'
            ];
        }
        

        $selectedCompany = CompanyUser::where('user_id', $user->id)
            ->where('status', 1)
            ->with('company')
            ->first();

        if (!$selectedCompany) {
            abort(response()->json(['message' => 'Active company not found.'], 422));
        }

        return $selectedCompany;
    }

    public static function getCompanyIdOrFail()
    {
        $company = self::getSelectedCompanyOrFail();
        if ($company instanceof \Illuminate\Http\JsonResponse) {
            return $company; 
        }

        return $company->company_id;
    }
}
