<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('permissions')->get();
    
        return response()->json([
            'message'   => 'Roles retrieved successfully.',
            'roles'     => $roles,
            'total'     => $roles->count(),
        ], 200);
    }
    

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|unique:roles,name',
            'guard_name'   => 'nullable|string',
            'permissions'  => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $data['guard_name'] = $data['guard_name'] ?? 'web';
        $data['company_id'] = Auth::user()->company_id;

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
            'name'         => 'required|string|unique:roles,name,' . $role->id,
            'guard_name'   => 'nullable|string',
            'permissions'  => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
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
