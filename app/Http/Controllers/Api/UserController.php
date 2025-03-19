<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the users with role "user".
     */
    public function index()
    {
        // Retrieve all users with their roles.
        $users = User::with('roles')->get();

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
        // Validate the incoming request without a UID field.
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'number'   => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Always generate a unique UID based on the last user's UID.
        $lastUser = User::orderBy('id', 'desc')->first();
        if ($lastUser && $lastUser->uid) {
            $lastNumber = (int) substr($lastUser->uid, 3);
            $newNumber  = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        $uid = 'AMT' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);

        $user = User::create([
            'uid'      => $uid,
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'number'   => $request->number,
        ]);

        // Always assign the default role "user"
        $user->assignRole('user');

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
        $user = User::with('roles')->findOrFail($id);

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

        // Do not allow updating the UID.
        $validator = Validator::make($request->all(), [
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:8',
            'number'   => 'sometimes|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $updateData = [];
        if ($request->has('name')) {
            $updateData['name'] = $request->name;
        }
        if ($request->has('email')) {
            $updateData['email'] = $request->email;
        }
        if ($request->has('password')) {
            $updateData['password'] = Hash::make($request->password);
        }
        if ($request->has('number')) {
            $updateData['number'] = $request->number;
        }

        $user->update($updateData);

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
 * Get the authenticated user.
 */
public function authUser(Request $request)
{
    return response()->json([
        'message' => 'Authenticated user retrieved successfully.',
        'user'    => new UserResource($request->user()->load('roles', 'companies')),
    ]);
}

    
}
