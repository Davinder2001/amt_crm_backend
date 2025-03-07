<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->get();
        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|unique:roles,name',
            'guard_name'   => 'sometimes|string',
            'permissions'  => 'sometimes|array',
            'permissions.*'=> 'string|exists:permissions,name'
        ]);

        $data['guard_name'] = $data['guard_name'] ?? 'web';
        $data['company_id'] = $request->user()->company_id;

        $role = Role::create($data);

        if (!empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return response()->json($role->load('permissions'), 201);
    }

    public function show(Role $role)
    {
        return response()->json($role->load('permissions'));
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name'         => 'required|string|unique:roles,name,'.$role->id,
            'guard_name'   => 'sometimes|string',
            'permissions'  => 'sometimes|array',
            'permissions.*'=> 'string|exists:permissions,name'
        ]);

        $data['guard_name'] = $data['guard_name'] ?? 'web';
        $role->update($data);

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return response()->json($role->load('permissions'));
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return response()->json(['message' => 'Role deleted successfully']);
    }
}
