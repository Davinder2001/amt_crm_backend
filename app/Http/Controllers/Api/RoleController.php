<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

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
            'name'       => 'required|string|unique:roles,name',
            'guard_name' => 'sometimes|string',
        ]);

        $data['guard_name'] = $data['guard_name'] ?? 'web';
        $role = Role::create($data);

        return response()->json($role, 201);
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
            'name'       => 'required|string|unique:roles,name,'.$role->id,
            'guard_name' => 'sometimes|string',
        ]);

        $data['guard_name'] = $data['guard_name'] ?? 'web';
        $role->update($data);

        return response()->json($role);
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
