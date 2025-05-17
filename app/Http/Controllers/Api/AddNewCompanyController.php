<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Package;
use App\Services\CompanyIdService;

class AddNewCompanyController extends Controller
{
    /**
     * Initiates PhonePe payment after validating company registration data
     */
    public function paymentInitiate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'email'                 => 'required|email',
            'company_name'          => 'required|string|max:255',
            'company_id'            => 'nullable|string|max:50|unique:companies,company_id',
            'company_logo'          => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            // 'package_id'            => 'required|exists:packages,id',
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
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $merchantOrderId = 'ORDER_' . uniqid();
        $package = Package::find($data['package_id'] ?? 1);
        $amount = 100 * $package->price;

        // Get OAuth token
        $oauthResponse = Http::asForm()->post('https://api-preprod.phonepe.com/apis/pg-sandbox/v1/oauth/token', [
            'client_id'      => 'TEST-M22CCW231A75L_25050',
            'client_version' => '1',
            'client_secret'  => 'ZmVjZWMwNWYtNzk4Ny00MWY4LTkzNGItNTA3MWQxNzZiODI5',
            'grant_type'     => 'client_credentials',
        ]);

        if (!$oauthResponse->ok() || !$oauthResponse->json('access_token')) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get PhonePe access token',
                'details' => $oauthResponse->json()
            ], 500);
        }

        $accessToken = $oauthResponse->json('access_token');
        $callbackUrl = "http://localhost:3000/payment-confirm?orderId={$merchantOrderId}";
        $redirectUrl = "http://localhost:3000/payment-confirm?orderId={$merchantOrderId}";

        $checkoutPayload = [
            "merchantOrderId" => $merchantOrderId,
            "amount"          => $amount,
            "paymentFlow"     => [
                "type" => "PG_CHECKOUT",
                "merchantUrls" => [
                    "redirectUrl" => $redirectUrl,
                    "callbackUrl" => $callbackUrl
                ]
            ]
        ];

        $checkoutResponse = Http::withHeaders([
            'Authorization' => 'O-Bearer ' . $accessToken,
            'Content-Type'  => 'application/json',
        ])->post('https://api-preprod.phonepe.com/apis/pg-sandbox/checkout/v2/pay', $checkoutPayload);

        $responseData = $checkoutResponse->json();
        $paymentUrl = $responseData['redirectUrl'] ?? $responseData['data']['redirectUrl'] ?? null;

        if (!$checkoutResponse->ok() || !$paymentUrl) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize payment',
                'response' => $responseData
            ], 500);
        }

        return response()->json([
            'success'          => true,
            'merchantOrderId'  => $merchantOrderId,
            'redirect_url'     => $paymentUrl
        ]);
    }

    /**
     * Store the company after payment is completed
     */
    public function store(Request $request, $orderId)
    {
        $validator = Validator::make($request->all(), [
            'company_name'          => 'required|string|max:255',
            'company_id'            => 'nullable|string|max:50|unique:companies,company_id',
            'company_logo'          => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'package_id'            => 'required|exists:packages,id',
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

        // Step 1: Verify payment status via PhonePe
        $oauthResponse = Http::asForm()->post('https://api-preprod.phonepe.com/apis/pg-sandbox/v1/oauth/token', [
            'client_id'      => 'TEST-M22CCW231A75L_25050',
            'client_version' => '1',
            'client_secret'  => 'ZmVjZWMwNWYtNzk4Ny00MWY4LTkzNGItNTA3MWQxNzZiODI5',
            'grant_type'     => 'client_credentials',
        ]);

        $accessToken = $oauthResponse->json('access_token');

        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Missing authorization token.'
            ], 401);
        }

        $statusResponse = Http::withHeaders([
            'Authorization' => 'O-Bearer ' . $accessToken,
            'Content-Type'  => 'application/json',
        ])->get("https://api-preprod.phonepe.com/apis/pg-sandbox/checkout/v2/order/{$orderId}/status");

        if (!$statusResponse->ok()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check payment status.',
                'details' => $statusResponse->json()
            ], 500);
        }

        $status = strtoupper($statusResponse->json('state'));
        if ($status !== 'COMPLETED') {
            return response()->json([
                'success' => false,
                'message' => 'Payment was not successful.',
                'payment_status' => $status
            ], 402);
        }

        // Step 2: Continue with company creation
        $slug = Str::slug($data['company_name']);
        if (Company::where('company_slug', $slug)->exists()) {
            throw ValidationException::withMessages([
                'company_name' => ['Company slug already exists. Please choose a different company name.']
            ]);
        }

        $frontPath = null;
        if ($request->hasFile('business_proof_front')) {
            $file = $request->file('business_proof_front');
            $name = uniqid('proof_front_') . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/business_proofs'), $name);
            $frontPath = 'uploads/business_proofs/' . $name;
        }

        $backPath = null;
        if ($request->hasFile('business_proof_back')) {
            $file = $request->file('business_proof_back');
            $name = uniqid('proof_back_') . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/business_proofs'), $name);
            $backPath = 'uploads/business_proofs/' . $name;
        }

        $logoPath = null;
        if ($request->hasFile('company_logo')) {
            $file = $request->file('company_logo');
            $name = uniqid('company_logo_') . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/business_proofs'), $name);
            $logoPath = 'uploads/business_proofs/' . $name;
        }

        $companyId = CompanyIdService::generateNewCompanyId();
        $company = Company::create([
            'company_id'            => $companyId,
            'company_name'          => $data['company_name'],
            'company_logo'          => $logoPath,
            'package_id'            => $data['package_id'],
            'company_slug'          => $slug,
            'payment_status'        => 'paid',
            'verification_status'   => 'pending',
            'business_address'      => $data['business_address'] ?? null,
            'pin_code'              => $data['pin_code'] ?? null,
            'business_proof_type'   => $data['business_proof_type'] ?? null,
            'business_id'           => $data['business_id'] ?? null,
            'business_proof_front'  => $frontPath,
            'business_proof_back'   => $backPath,
        ]);

        $user = Auth::user();

        CompanyUser::create([
            'user_id'    => $user->id,
            'company_id' => $company->id,
            'user_type'  => 'admin'
        ]);

        $role = Role::firstOrCreate([
            'name'       => 'admin',
            'guard_name' => 'web',
            'company_id' => $company->id,
        ]);

        $user->assignRole($role);

        return response()->json([
            'success' => true,
            'message' => 'Company created successfully after successful payment.',
            'company' => $company,
        ], 201);
    }
}
