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
use App\Models\CompanyUser;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

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

        $data = $validator->validated();

        $alreadyUser = User::where('number', $data['number'])->where('user_type', 'user')->exists();

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

    /**
     * Register a new admin.
     */
    public function adminRegister(AdminRegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $result = $this->registrationService->register($data);

            return response()->json([
                'message' => 'Admin registered successfully. Company and role assigned.',
                'user'    => new UserResource($result['user']->load('roles')),
                'company' => $result['company'],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
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
    
        $email = $request->input('email');
        $user = User::where('email', $email)->first();
    
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

        $email = $request->email;
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
