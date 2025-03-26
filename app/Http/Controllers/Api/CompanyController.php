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
    public function index()
    {
        return CompanyResource::collection(Company::all());
    }


    public function show($id)
    {
        return new CompanyResource(Company::findOrFail($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'required|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);

        return new CompanyResource(Company::create($data));
    }

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

    
    public function destroy($id)
    {
        Company::findOrFail($id)->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Company deleted successfully.'
        ]);
    }

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
    
        // ✅ Check if company relation exists and is verified
        if (!$companyUser->company || $companyUser->company->verification_status !== 'verified') {
            return response()->json(['error' => 'Company is not verified.'], 403);
        }
    
        // ✅ Clear all statuses and select the verified company
        CompanyUser::where('user_id', $user->id)->update(['status' => 0]);
        CompanyUser::where('user_id', $user->id)->where('company_id', $id)->update(['status' => 1]);
    
        return response()->json(['message' => 'Company selected successfully']);
    }
    

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
    
}
