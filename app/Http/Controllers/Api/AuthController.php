<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Services\AdminRegistrationService;
use App\Http\Requests\AdminRegisterRequest;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    protected AdminRegistrationService $registrationService;

    public function __construct(AdminRegistrationService $registrationService)
    {
        $this->registrationService = $registrationService;
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => 'user',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'      => 'User registered successfully.',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => new UserResource($user),
        ], 201);
    }


    public function adminRegister(AdminRegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
    
        try {
            $result = $this->registrationService->register($data);
            $result['user']->assignRole('admin');
    
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


    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'number'   => 'required|string',
            'password' => 'required|string',
        ]);
    
        $user = User::where('number', $data['number'])->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'number' => ['No user found with the provided mobile number.']
            ]);
        }
    
        if (!Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.']
            ]);
        }
    
        $token = $user->createToken('auth_token')->plainTextToken;
    
        return response()->json([
            'message'      => 'Logged in successfully.',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => new UserResource($user),
        ]);
    }
    

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    }
}
