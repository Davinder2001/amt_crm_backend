<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\PhonePePaymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Company;
use App\Services\CompanySetupService;
use App\Models\CompanyUser;
use App\Services\CompanyIdService;
use App\Models\Package;

class AddNewCompanyController extends Controller
{
    /**
     * Payment initiate and add commpany function
     */
    public function paymentInitiate(Request $request, PhonePePaymentService $paymentService)
    {
        $validator = Validator::make($request->all(), [
            'company_name'          => 'required|string|max:255',
            'package_id'            => 'required|exists:packages,id',
            'business_category_id'  => 'required|exists:business_categories,id',
            'company_logo'          => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'subscription_type'     => 'required|in:annual,three_years', // 🔄 removed 'monthly'
            'business_address'      => 'nullable|string',
            'pin_code'              => 'nullable|string|max:10',
            'business_proof_type'   => 'nullable|string|max:255',
            'business_id'           => 'nullable|string|max:255',
            'business_proof_front'  => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'business_proof_back'   => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $slug = Str::slug($data['company_name']);

        if (Company::where('company_slug', $slug)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Company already exists. Payment not initiated.',
                'code'    => 'COMPANY_ALREADY_EXISTS'
            ], 409);
        }

        $merchantOrderId = 'ORDER_' . uniqid();
        $package = Package::findOrFail($data['package_id']);

        // ✅ Dynamically determine amount
        $subType = $data['subscription_type'];

        if ($subType === 'annual') {
            $amount = 100 * $package->annual_price;
        } elseif ($subType === 'three_years') {
            $amount = 100 * $package->three_years_price;
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid subscription type.',
            ], 400);
        }

        $result = $paymentService->initiateCompanyPayment($merchantOrderId, $amount);

        if (!$result['success']) {
            return response()->json($result, 500);
        }

        // ✅ File uploads
        $frontPath = $request->file('business_proof_front')?->storeAs(
            'uploads/business_proofs',
            uniqid('proof_front_') . '.' . $request->file('business_proof_front')->getClientOriginalExtension(),
            'public'
        );

        $backPath = $request->file('business_proof_back')?->storeAs(
            'uploads/business_proofs',
            uniqid('proof_back_') . '.' . $request->file('business_proof_back')->getClientOriginalExtension(),
            'public'
        );

        $logoPath = $request->file('company_logo')?->storeAs(
            'uploads/business_proofs',
            uniqid('company_logo_') . '.' . $request->file('company_logo')->getClientOriginalExtension(),
            'public'
        );

        $companyId = CompanyIdService::generateNewCompanyId();

        $company = Company::create([
            'company_id'            => $companyId,
            'company_name'          => $data['company_name'],
            'company_logo'          => $logoPath,
            'package_id'            => $data['package_id'],
            'subscription_type'     => $subType,
            'business_category'     => $data['business_category_id'],
            'company_slug'          => $slug,
            'payment_status'        => 'PENDING',
            'order_id'              => $merchantOrderId,
            'payment_recoad_status' => 'initiated',
            'verification_status'   => 'pending',
            'business_address'      => $data['business_address'] ?? null,
            'pin_code'              => $data['pin_code'] ?? null,
            'business_proof_type'   => $data['business_proof_type'] ?? null,
            'business_id'           => $data['business_id'] ?? null,
            'business_proof_front'  => $frontPath,
            'business_proof_back'   => $backPath,
            'subscription_date'     => now('Asia/Kolkata')->toDateTimeString(),
            'subscription_status'   => 'active',
        ]);

        CompanyUser::create([
            'user_id'    => Auth::id(),
            'company_id' => $company->id,
            'user_type'  => 'admin',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment initiated.',
            'payment' => $result,
            'company' => $company->company_name,
        ], 200);
    }


    /**
     * Confirm Payment and update payment status
     */
    public function confirmCompanyPayment($orderId)
    {
        $company = Company::where('order_id', $orderId)->first();

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'No matching company found for this order ID.',
            ], 404);
        }

        if ($company->payment_record_status === 'recorded') {
            return response()->json([
                'success' => true,
                'message' => 'Payment already recorded.',
                'company' => $company,
            ], 200);
        }

        $statusService = new PhonePePaymentService();
        $paymentCheck = $statusService->checkAndUpdateStatus($orderId);

        if (!$paymentCheck['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm payment.',
                'details' => $paymentCheck['message'] ?? 'Unknown error',
            ], 402);
        }

        if ($paymentCheck['status'] !== 'COMPLETED') {
            return response()->json([
                'success'        => false,
                'message'        => 'Payment was not successful.',
                'payment_status' => $paymentCheck['status'],
            ], 402);
        }

        $company->update([
            'payment_status'        => $paymentCheck['status'],
            'transaction_id'        => $paymentCheck['transaction_id'] ?? null,
            'payment_record_status' => 'recorded',
        ]);

        $user = Auth::user();
        CompanySetupService::setupDefaults($company, $user);

        return response()->json([
            'success' => true,
            'message' => 'Company payment confirmed and setup completed.',
            'company' => $company->company_name,
        ], 200);
    }



    /**
     * Get the status of company Payment and Verification 
     */
    public function getCompanyStatus($companyId)
    {
        $company = Company::where('id', $companyId)->first();

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'No company found with the provided company ID.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Company status retrieved successfully.',
            'data' => [
                'company_name'          => $company->company_name,
                'company_id'            => $company->company_id,
                'order_id'              => $company->order_id,
                'payment_status'        => $company->payment_status,
                'payment_recoad_status' => $company->payment_recoad_status,
                'subscription_status'   => $company->subscription_status,
                'verification_status'   => $company->verification_status,
                'subscription_date'     => $company->subscription_date,
            ],
        ], 200);
    }
}
