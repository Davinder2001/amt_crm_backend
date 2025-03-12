<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees (users with user_type 'employee').
     */
    public function index()
    {
        $employees = User::where('user_type', 'employee')->with(['roles.permissions', 'company'])->get();

        return response()->json([
            'message'    => 'Employees retrieved successfully.',
            'employees'  => UserResource::collection($employees),
            'total'      => $employees->count(),
        ], 200);
    }

    /**
     * Store a newly created employee.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone'    => 'required|string|max:20',
            'role_id'  => 'required|exists:roles,id',
        ]);

        $employee = User::create([
            'name'       => $data['name'],
            'email'      => $data['email'],
            'password'   => Hash::make($data['password']),
            'company_id' => $request->user()->company_id,
            'phone'      => $data['phone'],
            'role_id'    => $data['role_id'],
            'user_type'  => 'employee',
        ]);

        return response()->json([
            'message'   => 'Employee created successfully.',
            'employee'  => new UserResource($employee->load('role')),
        ], 201);
    }

    /**
     * Display the specified employee (only if user_type is 'employee').
     */
    public function show($id)
    {
        $employee = User::where('user_type', 'employee')->with(['role', 'company'])->findOrFail($id);

        return response()->json([
            'message'   => 'Employee retrieved successfully.',
            'employee'  => new UserResource($employee),
        ]);
    }

    /**
     * Update the specified employee.
     */
    public function update(Request $request, $id)
    {
        $employee = User::where('user_type', 'employee')->findOrFail($id);

        $data = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|string|email|max:255|unique:users,email,' . $employee->id,
            'password' => 'sometimes|string|min:8',
            'role_id'  => 'sometimes|exists:roles,id',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $employee->update($data);

        return response()->json([
            'message'   => 'Employee updated successfully.',
            'employee'  => new UserResource($employee->load('role')),
        ]);
    }

    /**
     * Remove the specified employee.
     */
    public function destroy($id)
    {
        $employee = User::where('user_type', 'employee')->findOrFail($id);
        $employee->delete();

        return response()->json(['message' => 'Employee deleted successfully.']);
    }
}
