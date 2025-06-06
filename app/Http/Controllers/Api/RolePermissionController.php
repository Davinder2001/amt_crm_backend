<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Http\JsonResponse;

class RolePermissionController extends Controller
{
    /**
     * Assign a role to the specified user.
     */
    public function assignRole(Request $request, User $user, $id): JsonResponse
    {
       $validator = Validator::make($request->all(), [
            'role' => 'required|string|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $user->assignRole($data['role']);


        return response()->json([
            'message' => 'Role assigned successfully',
            'user'    => $user->load('roles'),
        ]);
    }

    /**
     * Remove a role from the specified user.
     */
    public function removeRole(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|string|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $user->removeRole($data['role']);

        return response()->json([
            'message' => 'Role removed successfully',
            'user'    => $user->load('roles'),
        ]);
    }

    /**
     * Assign a permission to the specified role.
     */
    public function assignPermissionToRole(Request $request, Role $role): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'permission' => 'required|string|exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $role->givePermissionTo($data['permission']);

        return response()->json([
            'message' => 'Permission assigned to role successfully',
            'role'    => $role->load('permissions'),
        ]);
    }

    /**
     * Remove a permission from the specified role.
     */
    public function removePermissionFromRole(Request $request, Role $role): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'permission' => 'required|string|exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $role->revokePermissionTo($data['permission']);

        return response()->json([
            'message' => 'Permission removed from role successfully',
            'role'    => $role->load('permissions'),
        ]);
    }

    /**
     * Update the user's role by synchronizing roles.
     */
    public function updateRole(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|string|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $user->syncRoles([$data['role']]);

        return response()->json([
            'message' => 'User role updated successfully',
            'user'    => $user->load('roles'),
        ]);
    }
}
