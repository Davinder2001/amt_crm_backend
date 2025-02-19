<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmpRoleResource;
use App\Models\EmpRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class EmpRoleController extends Controller
{
    /**
     * Display a listing of the employee roles.
     */
    public function index()
    {
        $roles = EmpRoleResource::collection(EmpRole::all());

        return response()->json([
            'message' => 'Employee roles retrieved successfully.',
            'roles' => $roles,
            'length' => $roles->count(),
        ], 200);
    }

    /**
     * Store a newly created employee role in storage.
     */
    public function store(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'role_name' => 'required|string|max:255|unique:emp_roles',
                'admin_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $role = EmpRole::create([
                'role_name' => $request->role_name,
                'admin_id' => $request->admin_id,
            ]);

            return response()->json([
                'message' => 'Role created successfully!',
                'role' => new EmpRoleResource($role),
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified employee role.
     */
    public function show($id)
    {
        $role = EmpRole::find($id);

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        return new EmpRoleResource($role);
    }

    /**
     * Update the specified employee role in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $role = EmpRole::find($id);

            if (!$role) {
                return response()->json(['message' => 'Role not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'role_name' => 'sometimes|string|max:255|unique:emp_roles,role_name,' . $role->id,
                'admin_id' => 'sometimes|required',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $role->update([
                'role_name' => $request->role_name ?? $role->role_name,
                'admin_id' => $request->admin_id ?? $role->admin_id,
            ]);

            return response()->json([
                'message' => 'Role updated successfully!',
                'role' => new EmpRoleResource($role),
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified employee role from storage.
     */
    public function destroy($id)
    {
        $role = EmpRole::find($id);

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $role->delete();

        return response()->json(['message' => 'Role deleted successfully!'], 200);
    }
}
