<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use Spatie\Permission\Models\Role as SpatieRole;

class RolePermissionController extends Controller
{
    /**
     * Retrieve the user by ID or return a JSON error response.
     */
    protected function findUserOrFail($id)
    {
        $user = User::find($id);
        if (!$user) {
            abort(response()->json(['message' => 'User not found'], 404));
        }
        return $user;
    }

    /**
     * Assign a role to the specified user.
     */
    public function assignRole(Request $request, $id)
    {
        $user = $this->findUserOrFail($id);

        $data = $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user->assignRole($data['role']);

        return response()->json([
            'message' => 'Role assigned successfully',
            'user'    => $user->load('roles'),
        ]);
    }

    /**
     * Remove a role from the specified user.
     */
    public function removeRole(Request $request, $id)
    {
        $user = $this->findUserOrFail($id);

        $data = $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user->removeRole($data['role']);

        return response()->json([
            'message' => 'Role removed successfully',
            'user'    => $user->load('roles'),
        ]);
    }

    /**
     * Assign a permission to the specified role.
     */
   
public function assignPermissionToRole(Request $request, $roleId)
{
    // Retrieve the role by its ID. Using find() returns null if not found.
    $role = Role::find($roleId);
    if (!$role) {
        return response()->json(['message' => 'Role not found'], 404);
    }

    // Validate that the request contains a permission id that exists in the permissions table.
    $validator = Validator::make($request->all(), [
        'permission' => 'required|integer|exists:permissions,id'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation failed',
            'errors'  => $validator->errors()
        ], 422);
    }

    $data = $validator->validated();

    // Retrieve the permission by its ID.
    $permission = Permission::findOrFail($data['permission']);

    // Assign the permission to the role.
    $role->givePermissionTo($permission);

    return response()->json([
        'message' => 'Permission assigned to role successfully',
        'role'    => $role,
    ]);
}
    
    /**
     * Remove a permission from the specified role.
     */
    public function removePermissionFromRole(Request $request, $roleName)
    {
        try {
            $role = SpatieRole::findByName($roleName);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $data = $request->validate([
            'permission' => 'required|string|exists:permissions,name'
        ]);

        $role->revokePermissionTo($data['permission']);

        return response()->json([
            'message' => 'Permission removed from role successfully',
            'role'    => $role,
        ]);
    }

    /**
     * Update the user's role by synchronizing roles.
     */
    public function updateRole(Request $request, $id)
    {
        $user = $this->findUserOrFail($id);

        $data = $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        // Remove any existing roles and assign the new one.
        $user->syncRoles([$data['role']]);

        return response()->json([
            'message' => 'User role updated successfully',
            'user'    => $user->load('roles'),
        ]);
    }
}
