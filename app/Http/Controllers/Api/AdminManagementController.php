<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

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
            'admins' => $admins
        ]);
    }

    /**
     * Update the status of an admin.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:active,blocked,warning',
        ]);

        $user = User::role('admin')->findOrFail($id);
        $user->status = $request->status;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Admin status updated.',
            'data' => $user
        ]);
    }
}
