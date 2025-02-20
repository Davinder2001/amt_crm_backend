<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdminResource;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Exception;


class AdminCreateController extends Controller
{
    /**
     * Display a list of all admins.
     */
    public function index()
    {
        $admins = AdminResource::collection(Admin::all());

        return response()->json([
            'message' => 'Admins retrieved successfully.',
            'admins' => $admins,
            'length' => $admins->count(),
        ], 200);
    }


    /**
     * Store a newly created admin (Register).
     */
    public function store(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:admins',
                'mobile_no' => 'required|string|max:15|unique:admins',
                'password' => 'required|string|min:6|confirmed',
                'aadhar_card_no' => 'required|string|max:20|unique:admins',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $admin = Admin::create([
                'name' => $request->name,
                'email' => $request->email,
                'mobile_no' => $request->mobile_no,
                'password' => Hash::make($request->password),
                'aadhar_card_no' => $request->aadhar_card_no,
            ]);

            return response()->json([
                'message' => 'Admin registered successfully',
                'admin' => new AdminResource($admin),
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Display the specified admin.
     */
    public function show($id)
    {
        $admin = Admin::find($id);

        if (!$admin) {
            return response()->json(['message' => 'Admin not found'], 404);
        }

        return new AdminResource($admin);
    }



    /**
     * Update the specified admin.
     */
    public function update(Request $request, $id)
    {
        try {
            $admin = Admin::find($id);

            if (!$admin) {
                return response()->json(['message' => 'Admin not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|string|email|max:255|unique:admins,email,' . $id,
                'mobile_no' => 'sometimes|required|string|max:15|unique:admins,mobile_no,' . $id,
                'password' => 'sometimes|required|string|min:6|confirmed',
                'aadhar_card_no' => 'sometimes|required|string|max:20|unique:admins,aadhar_card_no,' . $id,
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $admin->update([
                'name' => $request->name ?? $admin->name,
                'email' => $request->email ?? $admin->email,
                'mobile_no' => $request->mobile_no ?? $admin->mobile_no,
                'password' => $request->password ? Hash::make($request->password) : $admin->password,
                'aadhar_card_no' => $request->aadhar_card_no ?? $admin->aadhar_card_no,
            ]);

            return response()->json([
                'message' => 'Admin updated successfully',
                'admin' => new AdminResource($admin),
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * Remove the specified admin.
     */
    public function destroy($id)
    {
        $admin = Admin::find($id);

        if (!$admin) {
            return response()->json(['message' => 'Admin not found'], 404);
        }

        $admin->delete();

        return response()->json(['message' => 'Admin deleted successfully'], 200);
    }
}
