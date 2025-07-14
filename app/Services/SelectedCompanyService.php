<?php

namespace App\Services;

use App\Models\CompanyUser;
use Illuminate\Support\Facades\Auth;

class SelectedCompanyService
{
    public static function getSelectedCompanyOrFail()
    {
        if (!Auth::check()) {
            abort(response()->json([
                'error' => 'Unauthorized',
                'message' => 'You are not authorized to access this resource.'
            ], 401));
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

        $token = $user->currentAccessToken();
        $activeCompanyId = $token?->active_company_id ?? null;

        if (!$activeCompanyId) {
            abort(response()->json([
                'message' => 'Selected company not provided in token.'
            ], 400));
        }

        $selectedCompany = CompanyUser::where('user_id', $user->id)
            ->where('company_id', $activeCompanyId)
            ->with('company')
            ->first();

        if (!$selectedCompany) {
            abort(response()->json([
                'message' => 'Active company not found or unauthorized.'
            ], 422));
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
