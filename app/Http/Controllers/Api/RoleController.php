<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role; // Using our custom Role model
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->get();
        return response()->json($roles);
    }

    public function store(Request $request)
    {
        // Validate input, including an optional 'permissions' array.
        $data = $request->validate([
            'name'        => 'required|string|unique:roles,name',
            'guard_name'  => 'sometimes|string',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        // Automatically set guard_name to 'web' if not provided.
        $data['guard_name'] = $data['guard_name'] ?? 'web';

        // Set the company_id automatically from authenticated user.
        $data['company_id'] = $request->user()->company_id;

        // Create the role.
        $role = Role::create($data);

        // If permissions are provided, assign them to the role.
        if (!empty($data['permissions'])) {
            // If your Role model extends Spatie's Role model, givePermissionTo() will be available.
            if (method_exists($role, 'givePermissionTo')) {
                $role->givePermissionTo($data['permissions']);
            } else {
                // Alternatively, if you handle it manually via a relationship:
                $permissions = Permission::whereIn('name', $data['permissions'])->get();
                $role->permissions()->attach($permissions);
            }
        }

        return response()->json($role->load('permissions'), 201);
    }

    public function show($id)
    {
        $role = Role::with('permissions')->find($id);
        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }
        return response()->json($role);
    }

    public function update(Request $request, $id)
    {
        $role = Role::find($id);
        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $data = $request->validate([
            'name'        => 'required|string|unique:roles,name,'.$role->id,
            'guard_name'  => 'sometimes|string',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        $data['guard_name'] = $data['guard_name'] ?? 'web';
        $role->update($data);

        // Optionally, update role permissions if provided.
        if (isset($data['permissions'])) {
            // Sync the new permissions list.
            $role->syncPermissions($data['permissions']);
        }

        return response()->json($role->load('permissions'));
    }

    public function destroy($id)
    {
        $role = Role::find($id);
        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }
        $role->delete();
        return response()->json(['message' => 'Role deleted successfully']);
    }
}
