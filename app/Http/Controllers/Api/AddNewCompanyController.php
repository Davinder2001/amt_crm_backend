<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use App\Models\Company;
use App\Models\Payment;
use App\Models\CompanyUser;
use App\Models\Package;
use App\Services\SelectedCompanyService;
use App\Services\CompanyIdService;

class AddNewCompanyController extends Controller
{
    /**
     * Initiates PhonePe payment after validating company registration data
     */
    public function paymentInitiate(Request $request)
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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors()
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

        $merchantOrderId    = 'ORDER_' . uniqid();
        $package            = Package::find($data['package_id']);
        $amount             = 100 * $package->price;

        $oauthResponse = Http::asForm()->post(env('PHONEPE_OAUTH_URL'), [
            'client_id'      => env('PHONEPE_CLIENT_ID'),
            'client_version' => env('PHONEPE_CLIENT_VERSION'),
            'client_secret'  => env('PHONEPE_CLIENT_SECRET'),
            'grant_type'     => env('PHONEPE_GRANT_TYPE'),
        ]);

        if (!$oauthResponse->ok() || !$oauthResponse->json('access_token')) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get PhonePe access token',
                'details' => $oauthResponse->json()
            ], 500);
        }

        $activeCompanyId = SelectedCompanyService::getSelectedCompanyOrFail();
        $companySlug     = $activeCompanyId->company->company_slug;





        $accessToken = $oauthResponse->json('access_token');
       $host = request()->getHost(); // returns domain like 'localhost' or 'amt.sparkweb.co.in'

if (str_contains($host, 'localhost')) {
    $baseUrl = env('PHONEPE_CALLBACK_BASE_URL_COMPANY');
    $callbackUrl = env('PHONEPE_CALLBACK_BASE_URL');
} elseif (str_contains($host, 'amt.sparkweb.co.in')) {
    $baseUrl = env('PHONEPE_CALLBACK_BASE_URL_COMPANY_PROD');
    $callbackUrl = env('PHONEPE_CALLBACK_BASE_URL_PROD');
} else {
    // Optional fallback if needed
    $baseUrl = env('PHONEPE_CALLBACK_BASE_URL_COMPANY_PROD');
    $callbackUrl = env('PHONEPE_CALLBACK_BASE_URL_PROD');
}

        $callbackUrl = "http://localhost:8000/api/v1/add-new-company/{$merchantOrderId}";
        $redirectUrl = "{$baseUrl}/$companySlug/confirm-company-payment/?orderId={$merchantOrderId}";

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
        ])->post(env('PHONEPE_CHECKOUT_URL'), $checkoutPayload);

        $responseData   = $checkoutResponse->json();
        $paymentUrl     = $responseData['redirectUrl'] ?? $responseData['data']['redirectUrl'] ?? null;

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
            'company_logo'          => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'package_id'            => 'required|exists:packages,id',
            'business_category_id'  => 'required|exists:business_categories,id',
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

        $data            = $validator->validated();
        $recoadedPayment = Company::where('order_id', $orderId)->where('payment_recoad_status', 'recorded')->first();

        if ($recoadedPayment) {
            return response()->json([
                'success' => true,
                'message' => 'Payment recorded. Company already exists.',
                'company' => $recoadedPayment,
            ], 201);
        }

        $oauthResponse = Http::asForm()->post(env('PHONEPE_OAUTH_URL'), [
            'client_id'      => env('PHONEPE_CLIENT_ID'),
            'client_version' => env('PHONEPE_CLIENT_VERSION'),
            'client_secret'  => env('PHONEPE_CLIENT_SECRET'),
            'grant_type'     => env('PHONEPE_GRANT_TYPE'),
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
        ])->get(env('PHONEPE_STATUS_URL') . "/{$orderId}/status");

        if (!$statusResponse->ok()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check payment status.',
                'details' => $statusResponse->json()
            ], 500);
        }

        $status             = strtoupper($statusResponse->json('state'));
        $paymentCheck       = $statusResponse->json();
        $paymentMode        = isset($paymentCheck['paymentDetails'][0]['paymentMode']) ? $paymentCheck['paymentDetails'][0]['paymentMode'] : null;
        $transactionId      = $paymentCheck['orderId'];
        $userId             = Auth::id();
        $transactionAmount  = $paymentCheck['amount'] / 100;

        $orderIdExists = Payment::where('order_id', $orderId)->exists();


        if (!$orderIdExists) {

            $nowIST = Carbon::now('Asia/Kolkata');
            Payment::create([
                'user_id'              => $userId,
                'order_id'             => $orderId,
                'transaction_id'       => $transactionId,
                'payment_status'       => $status,
                'payment_method'       => $paymentMode,
                'payment_reason'       => 'Company registration for package ID ' . $data['package_id'],
                'payment_fail_reason'  => null,
                'transaction_amount'   => $transactionAmount,
                'payment_date'         => $nowIST->format('d/m/Y'),
                'payment_time'         => $nowIST->format('h:i A'),
            ]);
        }

        if ($status !== 'COMPLETED') {
            return response()->json([
                'success' => false,
                'message' => 'Payment was not successful.',
                'payment_status' => $status
            ], 402);
        }


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
        $subscriptionDate = now()->setTimezone('Asia/Kolkata')->toDateTimeString();
        $companyId  = CompanyIdService::generateNewCompanyId();
        $company    = Company::create([
            'company_id'            => $companyId,
            'company_name'          => $data['company_name'],
            'company_logo'          => $logoPath,
            'package_id'            => $data['package_id'],
            'business_category'     => $data['business_category_id'],
            'company_slug'          => $slug,
            'payment_status'        => 'completed',
            'order_id'              => $orderId,
            'transation_id'         => $statusResponse['orderId'],
            'payment_recoad_status' => 'recorded',
            'verification_status'   => 'pending',
            'business_address'      => $data['business_address'] ?? null,
            'pin_code'              => $data['pin_code'] ?? null,
            'business_proof_type'   => $data['business_proof_type'] ?? null,
            'business_id'           => $data['business_id'] ?? null,
            'business_proof_front'  => $frontPath,
            'business_proof_back'   => $backPath,
            'subscription_date'     => $subscriptionDate,
            'subscription_status'   => 'active',
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


    public function upgradePackage(Request $request, $id)
    {
        // dd($id);
        $activeCompany      = SelectedCompanyService::getSelectedCompanyOrFail();
        $packageId          = $activeCompany->company->package_id;  
        dd($activeCompany->company->id);
    }
}
