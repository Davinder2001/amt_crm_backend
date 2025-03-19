<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
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


   public function selectedCompanies($id)
   {
       $company = Company::find($id);
   
       if (!$company) {
           return response()->json(['error' => 'Company not found'], 404);
       }
   
       // Store both company details and authenticated user ID in the session
       session()->put('selected_company', [
           'id'   => $company->id,
           'name' => $company->company_name,
           'user_id' => Auth::id(), // Store the authenticated user's ID
       ]);
   
       $selectedCompany = session('selected_company');
       
       return response()->json([
           'message' => 'Selected company set successfully.',
           'selected_company' => $selectedCompany,
       ]);
   }
   
   
   public function getSelectedCompanies(Request $request)
   {
       if (!Auth::check()) {
           return response()->json(['error' => 'Unauthorized'], 401);
       }
   
       $user = Auth::user();
   
       $selectedCompany = session('selected_company');
   
       if (!$selectedCompany) {
           return response()->json(['error' => 'No selected company set'], 404);
       }
   
       // Check that the session's user ID matches the authenticated user's ID
       if ($user->id !== $selectedCompany['user_id']) {
           return response()->json([
               'error' => 'You are not authorized to access this company.'
           ], 403);
       }
   
       return response()->json([
           'message' => 'Selected company retrieved successfully.',
           'selected_company' => $selectedCompany,
       ]);
   }
   
}
