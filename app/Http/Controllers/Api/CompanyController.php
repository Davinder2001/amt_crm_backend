<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Package;
use App\Models\Company;
use App\Models\CompanyAccount;
use App\Services\SelectedCompanyService;
use App\Models\CompanyUser;
use App\Models\BusinessCategory;
use Illuminate\Support\Facades\Validator;
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
        $user           = Auth::user();
        $companyUser    = CompanyUser::where('user_id', $user->id)->where('company_id', $id)->with('company')->first();

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

    /**
     * Get the names of all companies for a specific user.
     */
    public function getPendingCompanies()
    {
        $pendingCompanies = Company::where('payment_status', 'pending')->orWhere('verification_status', 'pending')->get();
        return CompanyResource::collection($pendingCompanies);
    }

    /**
     * Mark a company's payment as verified.
     */
    public function paymentStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'payment_status' => 'required|in:pending,completed,failed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $company = Company::findOrFail($id);
        $company->payment_status = $request->payment_status;
        $company->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Payment status updated successfully.',
            'data'    => new CompanyResource($company),
        ]);
    }

    /**
     * Mark a company's verification status as verified.
     */
    public function verificationStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'verification_status' => 'required|in:pending,verified,rejected,block',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $company = Company::findOrFail($id);
        $company->verification_status = $request->verification_status;
        $company->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Verification status updated successfully.',
            'data'    => new CompanyResource($company),
        ]);
    }

    /**
     * Get the details of the selected company, including its package and related packages.
     */
    public function companyDetails()
    {
        $selectedCompany   = SelectedCompanyService::getSelectedCompanyOrFail();
        $company           = Company::findOrFail($selectedCompany->company_id);
        $subscribedPackage = Package::with('limits')->find($company->package_id);
        $businessCategory  = BusinessCategory::find($company->business_category);
        $relatedPackages   = $businessCategory ? $businessCategory->packages()->with('limits')->get() : [];

        return response()->json([
            'company'            => $company,
            'subscribed_package' => $subscribedPackage,
            'related_packages'   => $relatedPackages,
        ]);
    }


    /**
     * Update company details (Admin can update after registration)
     */
    public function updateCompanyDetails(Request $request, $id)
    {
        $company = Company::findOrFail($id);

        $validator = Validator::make($request->all(), [
            // 'company_name'          => 'sometimes|string|max:255',
            'company_logo'          => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'company_signature'     => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'business_address'      => 'nullable|string',
            'business_id'           => 'nullable|string',
            'pin_code'              => 'nullable|string|max:10',
            'business_proof_type'   => 'nullable|string|max:255',
            'business_proof_front'  => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'business_proof_back'   => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'term_and_conditions'   => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
 
        $logoPath = $company->company_logo;
        $signaturePath = $company->company_signature;
        $frontPath = $company->business_proof_front;
        $backPath = $company->business_proof_back;

        if ($request->hasFile('company_logo')) {
            $logoPath = $request->file('company_logo')->storeAs(
                'uploads/business_proofs',
                uniqid('company_logo_') . '.' . $request->file('company_logo')->getClientOriginalExtension(),
                'public'
            );
        }

        if ($request->hasFile('company_signature')) {
            $signaturePath = $request->file('company_signature')->storeAs(
                'uploads/business_proofs',
                uniqid('company_signature_') . '.' . $request->file('company_signature')->getClientOriginalExtension(),
                'public'
            );
        }

        if ($request->hasFile('business_proof_front')) {
            $frontPath = $request->file('business_proof_front')->storeAs(
                'uploads/business_proofs',
                uniqid('proof_front_') . '.' . $request->file('business_proof_front')->getClientOriginalExtension(),
                'public'
            );
        }

        if ($request->hasFile('business_proof_back')) {
            $backPath = $request->file('business_proof_back')->storeAs(
                'uploads/business_proofs',
                uniqid('proof_back_') . '.' . $request->file('business_proof_back')->getClientOriginalExtension(),
                'public'
            );
        }

        $slug = $company->company_slug;
        if (!empty($data['company_name']) && $data['company_name'] !== $company->company_name) {
            $slug = Str::slug($data['company_name']);
            if (Company::where('company_slug', $slug)->where('id', '!=', $company->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Another company already exists with this name.',
                ], 409);
            }
        }

        $company->update([
            // 'company_name'          => $data['company_name'] ?? $company->company_name,
            'company_logo'          => $logoPath,
            'company_signature'     => $signaturePath,
            'company_slug'          => $slug,
            'business_address'      => $data['business_address'] ?? $company->business_address,
            'business_id'           => $data['business_id'] ?? $company->business_id,
            'pin_code'              => $data['pin_code'] ?? $company->pin_code,
            'business_proof_type'   => $data['business_proof_type'] ?? $company->business_proof_type,
            'business_proof_front'  => $frontPath,
            'business_proof_back'   => $backPath,
            'terms_and_conditions'  => $data['term_and_conditions'] ?? $company->terms_and_conditions,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Company details updated successfully.',
            'data'    => new CompanyResource($company), 
        ], 200);
    }

    /**
     * Get all accounts for the selected company.
     */
    public function getCompanyAccounts()
    {
        $company  = SelectedCompanyService::getSelectedCompanyOrFail();
        $accounts = CompanyAccount::where('company_id', $company->company->id)->get();

        return response()->json([
            'accounts' => $accounts,
        ]);
    }

    /**
     * Add a new account to the selected company.
     */
    public function addAccountsInCompany(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bank_name'      => 'required|string|max:255',
            'account_number' => 'required|string|max:30',
            'ifsc_code'      => 'required|string|max:20',
            'type'           => 'required|in:current,savings',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'validation_error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $company = SelectedCompanyService::getSelectedCompanyOrFail();
        $validated = $validator->validated();

        $account = new CompanyAccount([
            'company_id'     => $company->company->id,
            'bank_name'      => $validated['bank_name'],
            'account_number' => $validated['account_number'],
            'ifsc_code'      => $validated['ifsc_code'],
            'type'           => $validated['type'],
        ]);

        $account->save();

        return response()->json([
            'message' => 'Account added successfully',
            'account' => $account,
        ], 201);
    }

    /**
     * Get a single company account by ID.
     */
    public function getSingleCompanyAccount($id)
    {
        $company = SelectedCompanyService::getSelectedCompanyOrFail();
        $account = $company->accounts()->where('id', $id)->first();

        if (!$account) {
            return response()->json([
                'message' => 'Account not found.',
            ], 404);
        }

        return response()->json([
            'account' => $account,
        ]);
    }

    /**
     * Update an existing company account.
     */
    public function updateCompanyAccount(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'bank_name'      => 'sometimes|required|string|max:255',
            'account_number' => 'sometimes|required|string|max:30',
            'ifsc_code'      => 'sometimes|required|string|max:20',
            'type'           => 'sometimes|required|in:current,savings',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'validation_error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $company = SelectedCompanyService::getSelectedCompanyOrFail(); // must return Company
        $account = CompanyAccount::where('company_id', $company->company->id)->where('id', $id)->firstOrFail();

        $validated = $validator->validated();

        if (isset($validated['bank_name'])) {
            $account->bank_name = $validated['bank_name'];
        }
        if (isset($validated['account_number'])) {
            $account->account_number = $validated['account_number'];
        }
        if (isset($validated['ifsc_code'])) {
            $account->ifsc_code = $validated['ifsc_code'];
        }
        if (isset($validated['type'])) {
            $account->type = $validated['type'];
        }

        $account->save();

        return response()->json([
            'message' => 'Account updated successfully',
            'account' => $account,
        ]);
    }


    /**
     * Delete a company account.
     */
    public function deleteCompanyAccount($id)
    {
        $company = SelectedCompanyService::getSelectedCompanyOrFail(); // already the Company model

        $account = CompanyAccount::where('company_id', $company->id)
            ->where('id', $id)
            ->firstOrFail();

        $account->delete();

        return response()->json([
            'message' => 'Account deleted successfully',
        ]);
    }
}
