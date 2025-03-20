<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\CompanyUser;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\CompanyResource;


class CompanyController extends Controller
{
   public function index()
   {
       $companies = Company::all(); 
       return CompanyResource::collection($companies);
   }

   public function show($id)
   {
       $company = Company::findOrFail($id);
       return new CompanyResource($company);
   }

   public function store(Request $request)
   {
       $validated = $request->validate([
           'name' => 'required|string|max:255',
           'phone' => 'required|string|max:20',
           'address' => 'nullable|string|max:255',
       ]);

       $company = Company::create($validated);

       return new CompanyResource($company);
   }

   public function update(Request $request, $id)
   {
       $validated = $request->validate([
           'name' => 'sometimes|string|max:255',
           'phone' => 'sometimes|string|max:20',
           'address' => 'nullable|string|max:255',
       ]);

       $company = Company::findOrFail($id);
       $company->update($validated);
       return new CompanyResource($company);
   }

   public function destroy($id)
   {
       $company = Company::findOrFail($id);
       $company->delete();

       return response()->json([
           'status' => 'success',
           'message' => 'Company deleted successfully.'
       ]);
   }


   public function selectedCompanies(Request $request, $id)
   {
       $user = Auth::user();
       $companyId =  $id;
  
       $hasCompany = CompanyUser::where('user_id', $user->id)
       ->where('company_id', $companyId)
       ->exists();
       
   
       if (!$hasCompany) {
           return response()->json(['error' => 'Unauthorized company'], 403);
       }
   
       CompanyUser::where('user_id', $user->id)
                  ->update(['status' => 0]);
   
       CompanyUser::where('user_id', $user->id)
                  ->where('company_id', $companyId)
                  ->update(['status' => 1]);
   
       return response()->json(['message' => 'Company selected successfully']);
   }
   
   
   
   public function getSelectedCompanies(Request $request)
{
    if (!Auth::check()) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $user = Auth::user();

    // Get the active company for this user
    $selectedCompany = CompanyUser::where('user_id', $user->id)
                                  ->where('status', 1)
                                  ->with('company') 
                                  ->first();

    if (!$selectedCompany) {
        return response()->json(['error' => 'No active company selected'], 404);
    }

    return response()->json([
        'message' => 'Selected company retrieved successfully.',
        'selected_company' => $selectedCompany->company ?? null,
        'company_user_role' => $selectedCompany->role,
    ]);
}

}
