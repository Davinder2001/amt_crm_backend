<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\AdminManagementResource;

class AdminManagementController extends Controller
{
    /**
     * Get all users with 'admin' role.
     */
    public function index()
    {
        $admins = User::where('user_type', 'admin')->with(['roles.permissions'])->get();
    
        return response()->json([
            'status' => 'success',
            'admins' => AdminManagementResource::collection($admins),
        ]);
    }
    

    /**
     * Update the status of an admin.
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,blocked,warning',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }
    
        $user = User::where('user_type', 'admin')->find($id);
        $user->user_status = $request->status;
        $user->save();
    
        return response()->json([
            'status' => 'success',
            'message' => 'Admin status updated.',
            'data' => $user
        ]);
    }


    /**
     * View a single admin by ID.
     */
    public function show($id)
    {
        $admin = User::where('user_type', 'admin')->with(['roles.permissions', 'companies'])->find($id);

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Admin not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'admin' => new AdminManagementResource($admin),
        ]);
    }    
}
