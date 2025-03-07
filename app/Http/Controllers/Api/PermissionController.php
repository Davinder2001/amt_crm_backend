<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    /**
     * Display a listing of the permissions.
     */
    public function index(): JsonResponse
    {
        return response()->json(Permission::all());
    }

    /**
     * Store a newly created permission.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|unique:permissions,name|string|max:255',
        ]);

        $permission = Permission::create($data);

        return response()->json($permission, 201);
    }

    /**
     * Display the specified permission.
     */
    public function show(Permission $permission): JsonResponse
    {
        return response()->json($permission);
    }

    /**
     * Update the specified permission.
     */
    public function update(Request $request, Permission $permission): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|unique:permissions,name,' . $permission->id . '|string|max:255',
        ]);

        $permission->update($data);

        return response()->json($permission);
    }

    /**
     * Remove the specified permission.
     */
    public function destroy(Permission $permission): JsonResponse
    {
        $permission->delete();
        return response()->json(['message' => 'Permission deleted successfully']);
    }
}
