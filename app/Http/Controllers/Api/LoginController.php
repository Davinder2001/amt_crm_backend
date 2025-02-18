<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);


        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();



            $token = $user->createToken('AuthToken')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'user' => $user,
                'token' => $token
            ], 200);
        }

        return response()->json([
            'message' => 'Invalid credentials'
        ], 401);
    }


    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();


        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
