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

        $selectedCompany = CompanyUser::where('user_id', $user->id)
            ->where('status', 1)
            ->with('company')
            ->first();

        if (!$selectedCompany) {
            abort(response()->json(['message' => 'Active company not found.'], 422));
        }

        return $selectedCompany;
    }

    // Optional - Shortcut to get only company_id
    public static function getCompanyIdOrFail()
    {
        return self::getSelectedCompanyOrFail()->company_id;
    }
}
