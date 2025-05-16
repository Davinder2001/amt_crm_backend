<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Services\AdminRegistrationService;
use App\Http\Requests\AdminRegisterRequest;
use App\Models\User;
use Illuminate\Support\Str;
use App\Models\CompanyUser;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use PhonePe\Env;
use PhonePe\payments\v2\standardCheckout\StandardCheckoutClient;
use PhonePe\payments\v2\models\request\builders\StandardCheckoutPayRequestBuilder;

class AuthController extends Controller
{
    /**
     * @var AdminRegistrationService
     */
    protected AdminRegistrationService $registrationService;

    public function __construct(AdminRegistrationService $registrationService)
    {
        $this->registrationService = $registrationService;
    }


    /**
     * Register a new user.
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255',
            'password' => 'required|string|min:8',
            'number'   => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data           = $validator->validated();
        $alreadyUser    = User::where('number', $data['number'])->where('user_type', 'user')->exists();

        if ($alreadyUser) {
            return response()->json([
                'errors' => ['number' => ['You are already register kindly login.']]
            ], 422);
        }

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'number'   => $data['number'],
            'password' => Hash::make($data['password']),
            'uid'      => User::generateUid(),
        ]);

        $user->assignRole('user');
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'      => 'User registered successfully.',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => new UserResource($user),
        ], 201);
    }



    public function sendWpOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'number' => 'required|string|regex:/^[0-9]{10,15}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $otp                = rand(100000, 999999);
        $numberGet          = $request->number;
        $number             = 91 . $numberGet;
        $msg91AuthKey       = "451198A9qD8Lu26821c9a6P1";
        $integratedNumber   = '918219678757';
        $namespace          = 'c448fd19_1766_40ad_b98d_bae2703feb98';

        // ✅ Use raw number (without country code) for consistent caching
        Cache::put("otp_{$numberGet}", $otp, now()->addMinutes(5));

        $payload = [
            "integrated_number" => $integratedNumber,
            "content_type" => "template",
            "payload" => [
                "messaging_product" => "whatsapp",
                "type" => "template",
                "template" => [
                    "name" => "authentication",
                    "language" => [
                        "code" => "en_US",
                        "policy" => "deterministic"
                    ],
                    "namespace" => $namespace,
                    "to_and_components" => [
                        [
                            "to" => $number,
                            "components" => [
                                "body_1" => [
                                    "type" => "text",
                                    "value" => $otp
                                ],
                                "button_1" => [
                                    "subtype" => "url",
                                    "type" => "text",
                                    "value" => "COPY"
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'authkey' => $msg91AuthKey
        ])->post('https://api.msg91.com/api/v5/whatsapp/whatsapp-outbound-message/bulk/', $payload);

        if ($response->successful()) {
            $responseData = $response->json();

            return response()->json([
                'success'     => true,
                'number'      => $number,
                'request_id'  => $responseData['request_id'] ?? null,
                'message'     => 'OTP sent successfully'
            ]);
        }

        return response()->json([
            'success'  => false,
            'message'  => 'Failed to send OTP',
            'response' => $response->json()
        ], 500);
    }


    public function veriWpfyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'number'    => 'required|string|regex:/^[0-9]{10,15}$/',
            'otp'       => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $number = $request->number;
        $otp = $request->otp;

        $cachedOtp = Cache::get("otp_{$number}");

        if ($cachedOtp && $cachedOtp == $otp) {
            Cache::put("otp_verified_{$number}", true, now()->addMinutes(30));
            Cache::forget("otp_{$number}");

            return response()->json(['success' => true, 'message' => 'OTP verified successfully']);
        }

        return response()->json(['success' => false, 'message' => 'Invalid or expired OTP'], 400);
    }


    /**
     * Initiate admin registration and generate PhonePe payment link
     */
    public function adminRegisterInitiate(AdminRegisterRequest $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'  => 'required|email',
            'number' => 'required|string|max:15',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors()
            ], 422);
        }

        $merchantOrderId    = 'ORDER_' . uniqid();
        $amount             = 100 * 100; // fixed registration fee in paise

        // Get PhonePe access token
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

        // Set callback and redirect URLs
        $callbackUrl = "http://localhost:8000/api/v1/admin-register-confirm/$merchantOrderId";
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
     * Confirm registration after PhonePe payment
     */
    public function adminRegisterConfirm(AdminRegisterRequest $request, $id): JsonResponse
    {
        $orderId = $id;

        if (!$orderId) {
            return response()->json([
                'success' => false,
                'message' => 'Missing order ID.'
            ], 400);
        }


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

        try {
            $formData = $request->all();
            $result = $this->registrationService->register($formData);

            return response()->json([
                'success' => true,
                'message' => 'Admin registered successfully after payment.',
                'user'    => new UserResource($result['user']->load('roles')),
                'company' => $result['company'],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send OTP for email verification.
     */
    public function mailVerification(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $email  = $request->input('email');
        $user   = User::where('email', $email)->first();

        if ($user) {
            return response()->json(['message' => 'Email already registered.'], 409);
        }

        $otp = rand(100000, 999999);

        Cache::put("email_verification_{$email}", [
            'otp' => $otp,
            'expires_at' => now()->addMinutes(10)
        ], now()->addMinutes(10));

        Mail::send('emails.sendOtp', ['otp' => $otp, 'email' => $email], function ($message) use ($email) {
            $message->to($email)->subject('Your Verification Code');
        });

        return response()->json(['message' => 'Verification email sent successfully.']);
    }


    /**
     * Verify the OTP for registration.
     */
    public function verifyRegisterOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
        ]);

        $email      = $request->email;
        $cachedData = Cache::get("email_verification_{$email}");

        if (!$cachedData) {
            return response()->json(['message' => 'OTP not found or expired.'], 400);
        }

        if (now()->greaterThan($cachedData['expires_at'])) {
            Cache::forget("email_verification_{$email}");
            return response()->json(['message' => 'OTP has expired.'], 410);
        }

        if ($cachedData['otp'] != $request->otp) {
            return response()->json(['message' => 'Invalid OTP.'], 422);
        }

        Cache::forget("email_verification_{$email}");

        return response()->json(['message' => 'OTP verified successfully.']);
    }


    /**
     * Login a user.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'number'   => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $user = User::where('number', $data['number'])->where('user_type', 'user')->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['error' => 'Invalid credentials.'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'      => 'Logged in successfully.',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => new UserResource($user),
        ]);
    }


    /**
     * Login a company user.
     */
    public function companyLogin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'number'   => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $user = User::with(['companies', 'roles'])->where('number', $data['number'])->whereIn('user_type', ['employee', 'admin', 'super-admin'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['error' => 'Invalid credentials.'], 401);
        }

        if ($user->user_status === 'blocked') {
            return response()->json(['error' => 'Your account has been blocked. Please contact your administrator.'], 403);
        }

        $userCompanies = CompanyUser::where('user_id', $user->id)->get();

        if ($userCompanies->count() === 1) {
            $singleCompany = $userCompanies->first();
            $company = $singleCompany->company ?? $singleCompany->load('company')->company;

            if ($company && $company->verification_status === 'verified') {
                CompanyUser::where('user_id', $user->id)->update(['status' => 0]);
                CompanyUser::where('user_id', $user->id)->where('company_id', $singleCompany->company_id)->update(['status' => 1]);
            }
        }

        $activeTokens = $user->tokens()->where('expires_at', '>', now());
        if ($activeTokens->exists()) {
            $activeTokens->update(['expires_at' => now()]);
        }

        $tokenResult = $user->createToken('auth_token');
        $token = $tokenResult->plainTextToken;
        $accessToken = $tokenResult->accessToken;
        $accessToken->expires_at = now()->addHours(24);
        $accessToken->save();

        return response()->json([
            'message'      => 'Logged in successfully.',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => new UserResource($user),
        ]);
    }


    /**
     * Logout the user.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        CompanyUser::query()
            ->where('user_id', $user->id)
            ->update(['status' => 0]);

        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }


    /**
     * Refresh the token.
     */
    public function sendResetOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $otp = rand(100000, 999999);
        Cache::put('otp_' . $request->email, $otp, now()->addMinutes(10));

        Mail::raw("Your password reset OTP is: $otp", function ($message) use ($request) {
            $message->to($request->email)->subject('Password Reset OTP');
        });

        return response()->json(['message' => 'OTP sent successfully.']);
    }

    /**
     * Verify the OTP and reset the password.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'otp'      => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $storedOtp = Cache::get('otp_' . $request->email);

        if (!$storedOtp || $storedOtp != $request->otp) {
            return response()->json(['error' => 'Invalid OTP.'], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->update(['password' => Hash::make($request->password)]);
        Cache::forget('otp_' . $request->email);

        return response()->json(['message' => 'OTP verified and password reset successfully.']);
    }

    /**
     * Change the password.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->update(['password' => Hash::make($data['password'])]);

        return response()->json(['message' => 'Password reset successfully.']);
    }
}
