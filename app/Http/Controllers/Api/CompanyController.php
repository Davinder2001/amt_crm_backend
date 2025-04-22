<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Services\SelectedCompanyService;
use App\Models\CompanyUser;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\CompanyResource;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return CompanyResource::collection(Company::all());
    }


    /**
     * Display a listing of the resource.
     */
    public function show($id)
    {
        return new CompanyResource(Company::findOrFail($id));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'required|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);

        return new CompanyResource(Company::create($data));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $company = Company::findOrFail($id);
        $company->update($request->validate([
            'name'    => 'sometimes|string|max:255',
            'phone'   => 'sometimes|string|max:20',
            'address' => 'nullable|string|max:255',
        ]));

        return new CompanyResource($company);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        Company::findOrFail($id)->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Company deleted successfully.'
        ]);
    }

    /**
     * Select a company for the authenticated user.
     */
    public function selectedCompanies($id)
    {
        $user = Auth::user();

        $companyUser = CompanyUser::where('user_id', $user->id)
            ->where('company_id', $id)
            ->with('company')
            ->first();

        if (!$companyUser) {
            return response()->json(['error' => 'Unauthorized company'], 403);
        }

        if (!$companyUser->company || $companyUser->company->verification_status !== 'verified') {
            return response()->json(['error' => 'Company is not verified.'], 403);
        }

        CompanyUser::where('user_id', $user->id)->update(['status' => 0]);
        CompanyUser::where('user_id', $user->id)->where('company_id', $id)->update(['status' => 1]);

        return response()->json(['message' => 'Company selected successfully']);
    }


    /**
     * Get the selected company for the authenticated user.
     */
    public function getSelectedCompanies()
    {
        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();

        if (!$selectedCompany) {
            return response()->json(['error' => 'No active company selected'], 404);
        }

        if (isset($selectedCompany->super_admin) && $selectedCompany->super_admin === true) {
            return response()->json([
                'message'           => 'Selected company retrieved successfully.',
                'selected_company'  => 'Super Admin',
                'company_user_role' => 'super-admin',
            ]);
        }

        return response()->json([
            'message'           => 'Selected company retrieved successfully.',
            'selected_company'  => $selectedCompany->company ?? null,
            'company_user_role' => $selectedCompany->role,
        ]);
    }


    /**
     * Get the names of all companies.
     */
    public function getAllCompanyNames()
    {
        $companies = Company::all(['company_name']);

        return response()->json([
            'companies' => $companies,
        ]);
    }
}
