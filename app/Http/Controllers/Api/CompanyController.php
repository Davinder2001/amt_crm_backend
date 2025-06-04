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
        $selectedCompany    = SelectedCompanyService::getSelectedCompanyOrFail();
        $company            = Company::findOrFail($selectedCompany->company_id);
        $subscribedPackage  = Package::find($company->package_id);
        $businessCategory   = BusinessCategory::find($company->business_category);
        $relatedPackages    = $businessCategory ? $businessCategory->packages : [];

        return response()->json([
            'company'            => $company,
            'subscribed_package' => $subscribedPackage,
            'related_packages'   => $relatedPackages,
        ]);
    }

    /**
     * Get all accounts for the selected company.
     */
    public function getCompanyAccounts()
    {
        $company = SelectedCompanyService::getSelectedCompanyOrFail();

        $accounts = CompanyAccount::where('company_id', $company->id)->get();

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
            'company_id'     => $company->id,
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

        $company = SelectedCompanyService::getSelectedCompanyOrFail();
        $account = $company->accounts()->where('id', $id)->firstOrFail();
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
        $company = SelectedCompanyService::getSelectedCompanyOrFail();
        $account = $company->accounts()->where('id', $id)->firstOrFail();

        $account->delete();

        return response()->json([
            'message' => 'Account deleted successfully',
        ]);
    }
}
