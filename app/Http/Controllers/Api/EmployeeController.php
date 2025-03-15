<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\EmployeeResource;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees.
     */
    public function index()
    {
        $employees = User::where('user_type', 'employee')->with(['roles.permissions', 'company'])->get();

        return response()->json([
            'message'   => 'Employees retrieved successfully.',
            'employees' => EmployeeResource::collection($employees),
            'total'     => $employees->count(),
        ], 200);
    }

    /**
     * Store a newly created employee.
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name'     => 'required|string|max:255',
                'email'    => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'number'   => 'required|string|max:20',
                'role'     => 'required|exists:roles,name', // Ensure role exists
            ]);

            // Check if the phone number is already in use
            $existingUser = User::where('number', $data['number'])
                ->where('user_type', 'employee')
                ->where('user_status', 'active')
                ->first();

            if ($existingUser) {
                return response()->json([
                    'message' => 'This phone number is already in use by an active employee.',
                ], 400);
            }

            // Create the employee
            $employee = User::create([
                'name'       => $data['name'],
                'email'      => $data['email'],
                'password'   => Hash::make($data['password']),
                'company_id' => $request->user()->company_id ?? null,
                'number'     => $data['number'],
                'user_type'  => 'employee',
            ]);

            // Assign role using Spatie
            $employee->assignRole($data['role']);

            return response()->json([
                'message'  => 'Employee created successfully.',
                'employee' => new EmployeeResource($employee->load('roles')),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong. Please try again.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified employee.
     */
    public function show($id)
    {
        $employee = User::where('user_type', 'employee')->with(['roles.permissions', 'company'])->findOrFail($id);

        return response()->json([
            'message'  => 'Employee retrieved successfully.',
            'employee' => new EmployeeResource($employee),
        ]);
    }

    /**
     * Update the specified employee.
     */
    public function update(Request $request, $id)
    {
        $employee = User::where('user_type', 'employee')->findOrFail($id);

        try {
            // Validate input data
            $validator = Validator::make($request->all(), [
                'name'     => 'sometimes|string|max:255',
                'email'    => 'sometimes|string|email|max:255|unique:users,email,' . $employee->id,
                'password' => 'sometimes|string|min:8',
                'role'     => 'required|exists:roles,name', // Role is required when updating
                'number'   => 'sometimes|string|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            // Ensure role is provided
            if (!isset($data['role'])) {
                return response()->json([
                    'message' => 'Role is required when updating an employee.',
                ], 400);
            }

            // Update employee details
            $employee->update(Arr::except($data, ['role']));

            // Update role
            $employee->syncRoles($data['role']);

            return response()->json([
                'message'  => 'Employee updated successfully.',
                'employee' => new EmployeeResource($employee->load('roles')),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong. Please try again.',
                'error'   => $e->getMessage(),
            ], 500);
        }
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
