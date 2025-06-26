<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\PhonePePaymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use App\Models\Company;
use App\Models\Tax;
use App\Models\CompanyUser;
use App\Models\Shift;
use App\Services\CompanyIdService;
use App\Models\Package;

class AddNewCompanyController extends Controller
{
    /**
     * Initiates PhonePe payment after validating company registration data
     */
    public function paymentInitiate(Request $request, PhonePePaymentService $paymentService)
    {
        $validator = Validator::make($request->all(), [
            'company_name'          => 'required|string|max:255',
            'package_id'            => 'required|exists:packages,id',
            'business_category_id'  => 'required|exists:business_categories,id',
            'company_logo'          => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'business_address'      => 'nullable|string',
            'pin_code'              => 'nullable|string|max:10',
            'business_proof_type'   => 'nullable|string|max:255',
            'business_id'           => 'nullable|string|max:255',
            'business_proof_front'  => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'business_proof_back'   => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'subscription_type'     => 'required|in:monthly,annual',
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
        $package = Package::find($data['package_id']);
        $subType = $data['subscription_type'] ?? 'monthly';
        $amount  = ($subType === 'annual') ? 100 * $package->annual_price : 100 * $package->monthly_price;

        $result = $paymentService->initiateCompanyPayment($data, $merchantOrderId, $amount);

        return response()->json($result, $result['success'] ? 200 : 500);
    }



    /**
     * Store the company after payment is completed
     */
    public function store(Request $request, $orderId)
    {
        $validator = Validator::make($request->all(), [
            'company_name'          => 'required|string|max:255',
            'company_logo'          => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'package_id'            => 'required|exists:packages,id',
            'business_category_id'  => 'required|exists:business_categories,id',
            'subscription_type'     => 'required|string',
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
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $recoadedPayment = Company::where('order_id', $orderId)->where('payment_recoad_status', 'recorded')->first();
        if ($recoadedPayment) {
            return response()->json([
                'success' => true,
                'message' => 'Payment recorded. Company already exists.',
                'company' => $recoadedPayment,
            ], 201);
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
                'success' => false,
                'message' => 'Payment was not successful.',
                'payment_status' => $paymentCheck['status'],
            ], 402);
        }

        $slug = Str::slug($data['company_name']);
        if (Company::where('company_slug', $slug)->exists()) {
            throw ValidationException::withMessages([
                'company_name' => ['Company slug already exists. Please choose a different company name.']
            ]);
        }

        $frontPath = $request->file('business_proof_front')?->storeAs('uploads/business_proofs', uniqid('proof_front_') . '.' . $request->file('business_proof_front')->getClientOriginalExtension(), 'public');
        $backPath = $request->file('business_proof_back')?->storeAs('uploads/business_proofs', uniqid('proof_back_') . '.' . $request->file('business_proof_back')->getClientOriginalExtension(), 'public');
        $logoPath = $request->file('company_logo')?->storeAs('uploads/business_proofs', uniqid('company_logo_') . '.' . $request->file('company_logo')->getClientOriginalExtension(), 'public');

        $companyId = CompanyIdService::generateNewCompanyId();
        $company = Company::create([
            'company_id'            => $companyId,
            'company_name'          => $data['company_name'],
            'company_logo'          => $logoPath,
            'package_id'            => $data['package_id'],
            'subscription_type'     => $data['subscription_type'],
            'business_category'     => $data['business_category_id'],
            'company_slug'          => $slug,
            'payment_status'        => $paymentCheck['status'],
            'order_id'              => $orderId,
            'transation_id'         => $paymentCheck['transaction_id'],
            'payment_recoad_status' => 'recorded',
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

        $user = Auth::user();

        CompanyUser::create([
            'user_id'    => $user->id,
            'company_id' => $company->id,
            'user_type'  => 'admin'
        ]);

        Tax::create([
            'company_id' => $company->id,
            'name'       => 'GST',
            'rate'       => 18,
        ]);

        Shift::create([
            'company_id'     => $company->id,
            'shift_name'     => 'General Shift',
            'start_time'     => '09:00:00',
            'end_time'       => '18:00:00',
            'weekly_off_day' => 'Sunday',
        ]);

        $roles = ['admin', 'employee', 'hr', 'supervisor', 'sales'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate([
                'name'       => $roleName,
                'guard_name' => 'web',
                'company_id' => $company->id,
            ]);
        }

        $adminRole = Role::where([
            'name'       => 'admin',
            'guard_name' => 'web',
            'company_id' => $company->id,
        ])->first();

        $user->assignRole($adminRole);

        return response()->json([
            'success' => true,
            'message' => 'Company created successfully after successful payment.',
            'company' => $company,
        ], 201);
    }
}
