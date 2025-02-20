<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use App\Models\Admin;
use App\Models\SuperAdmin;

class LoginController extends Controller
{
    /**
     * Handle user login and generate a JWT token.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation Error',
                'messages' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only('email', 'password');
        $guards = ['super-admin', 'admin', 'api'];

        foreach ($guards as $guard) {
            if ($token = Auth::guard($guard)->attempt($credentials)) {
                return $this->respondWithToken($token, Auth::guard($guard)->user());
            }
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }


    /**
     * Return JWT Token Response with User Role
     */
    protected function respondWithToken($token, $user)
    {
        $role = match (get_class($user)) {
            SuperAdmin::class => 'super-admin',
            Admin::class => 'admin',
            User::class => 'user'
        };

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
            ],
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ]);
    }
}
