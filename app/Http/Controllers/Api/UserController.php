<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request)
    {
        // Check if the authenticated user has the "view_user" permission.
        if (!$request->user() || !$request->user()->can('view_user')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    
        $users = User::with(['roles.permissions', 'company'])->get();
    
        return response()->json([
            'message' => 'Users retrieved successfully.',
            'users'   => UserResource::collection($users),
            'total'   => $users->count(),
        ], 200);
    }
    


  /**
 * Store a newly created user.
 */
public function store(Request $request)
{
    $data = $request->validate([
        'name'     => 'required|string|max:255',
        'email'    => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8',
        'number'   => 'required|string|max:20', 
        'role'     => 'sometimes|string|exists:roles,name',
    ]);

    $user = User::create([
        'name'       => $data['name'],
        'email'      => $data['email'],
        'password'   => Hash::make($data['password']),
        'company_id' => $request->user()->company_id,
        'number'     => $data['number'], 
    ]);

    if (!empty($data['role'])) {
        $user->assignRole($data['role']);
    }

    return response()->json([
        'message' => 'User created successfully.',
        'user'    => new UserResource($user->load('roles')),
    ], 201);
}



    /**
     * Display the specified user.
     */
    public function show($id)
    {
        $user = User::with(['roles.permissions', 'company'])->findOrFail($id);

        return response()->json([
            'message' => 'User retrieved successfully.',
            'user'    => new UserResource($user),
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:8',
            'role'     => 'sometimes|string|exists:roles,name',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update(collect($data)->except('role')->toArray());

        if (!empty($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return response()->json([
            'message' => 'User updated successfully.',
            'user'    => new UserResource($user->load('roles')),
        ]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }

    /**
     * Assign a role to a user.
     */
    public function assignRole(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user->assignRole($data['role']);

        return response()->json([
            'message' => 'Role assigned successfully.',
            'user'    => new UserResource($user->load('roles')),
        ]);
    }

    /**
     * Update the role for a user.
     */
    public function updateRole(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user->syncRoles([$data['role']]);

        return response()->json([
            'message' => 'Role updated successfully.',
            'user'    => new UserResource($user->load('roles')),
        ]);
    }

    /**
     * Get the authenticated user.
     */
    public function authUser(Request $request)
    {
        return response()->json([
            'message' => 'Authenticated user retrieved successfully.',
            'user' => new UserResource($request->user()->load(['roles.permissions', 'company'])),
        ]);
    }
}
