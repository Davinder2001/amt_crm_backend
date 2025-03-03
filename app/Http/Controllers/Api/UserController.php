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
    $authUser = $request->user();
    if (!$authUser) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    $businessId = $authUser->buisness_id;

    // Fetch only users with the same business ID as the authenticated user.
    $users = User::with('roles.permissions')
                ->where('buisness_id', $businessId)
                ->get();

    return response()->json([
        'message' => 'Users retrieved successfully.',
        'users'   => UserResource::collection($users),
        'length'  => $users->count(),
    ], 200);
}

    public function store(Request $request)
    {
        // Get the authenticated user's business ID if available
        $authBusinessId = $request->user() ? $request->user()->buisness_id : null;

        // Validate only the fields coming from the request
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role'     => 'sometimes|required|string|exists:roles,name',
        ]);

        // Set buisness_id from the authenticated user, or null if not present
        $data['buisness_id'] = $authBusinessId;

        $user = User::create([
            'name'         => $data['name'],
            'email'        => $data['email'],
            'password'     => Hash::make($data['password']),
            'buisness_id'  => $data['buisness_id'],
        ]);

        if (isset($data['role'])) {
            $user->assignRole($data['role']);
            $user->load('roles');
        }

        return response()->json($user, 201);
    }


    /**
     * Display the specified user.
     */
    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Retrieve the authenticated user's business id, if available.
        $authBusinessId = $request->user() ? $request->user()->buisness_id : null;

        $data = $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'email'       => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'password'    => 'sometimes|required|string|min:8',
            'role'        => 'sometimes|required|string|exists:roles,name',
            'buisness_id' => 'sometimes|nullable|integer',
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $data['buisness_id'] = $authBusinessId ?? ($data['buisness_id'] ?? null);

        $userData = $data;
        unset($userData['role']);
        $user->update($userData);

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
            $user->load('roles');
        }

        return response()->json([
            'message' => 'User updated successfully',
            'user'    => $user,
        ]);
    }


    /**
     * Remove the specified user.
     */
    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * Assign a role to a user.
     */
    public function assignRole(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

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
     * Update the role for a user.
     */
    public function updateRole(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $data = $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user->syncRoles([$data['role']]);

        return response()->json([
            'message' => 'Role updated successfully',
            'user'    => $user->load('roles'),
        ]);
    }
}
