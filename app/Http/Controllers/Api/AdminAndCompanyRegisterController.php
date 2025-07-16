<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Services\OtpService;
use App\Models\User;

class AdminAndCompanyRegisterController extends Controller
{

    /**
     * Swnd OTP on whatsapp
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'required|string|regex:/^[0-9]{10,15}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $number = $request->number;
        $userType = 'admin';

        if (User::where('number', $number)->where('user_type', $userType)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This number is already registered.'
            ], 409);
        }

        $result = OtpService::send($number);

        if ($result['success']) {
            return response()->json([
                'success'    => true,
                'message'    => 'OTP sent successfully.',
                'request_id' => $result['request_id'],
                'number'     => $result['number']
            ]);
        }

        return response()->json([
            'success'  => false,
            'message'  => 'Failed to send OTP.',
            'response' => $result['error'] ?? null,
        ], 500);
    }



    /**
     * Register the admin with number email and pass
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'number'      => 'required|numeric|digits_between:10,15|unique:users,number',
            'email'       => 'required|email|max:255|unique:users,email',
            'password'    => 'required|string|min:8|confirmed',
            'otp'         => 'required|digits:6',
            'request_id'  => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $data    = $validator->validated();
        $otpData = Cache::get("otp_{$data['request_id']}");

        if (!$otpData || $otpData['otp'] != $data['otp'] || $otpData['number'] != $data['number']) {
            return response()->json([
                'message' => 'Invalid or expired OTP.',
            ], 401);
        }

        $user = User::create([
            'name'      => $data['name'],
            'number'    => $data['number'],
            'email'     => $data['email'],
            'user_type' => 'admin',
            'password'  => Hash::make($data['password']),
        ]);

        $user->assignRole('admin');
        Cache::forget("otp_{$data['request_id']}");
        $token = $user->createToken('auth_token')->plainTextToken;


        Mail::send('emails.admin_registered', ['user' => $user], function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Welcome to AMT CRM');
        });


        return response()->json([
            'message' => 'Admin user registered and logged in successfully.',
            'user'    => $user,
            'token'   => $token,
            'token_type' => 'Bearer'
        ], 201);
    }
}
