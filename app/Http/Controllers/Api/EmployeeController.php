<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\EmployeeResource;
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
            'employees'  => EmployeeResource::collection($employees),
            'total'      => $employees->count(),
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
                'role_id'  => 'required|exists:roles,id',
            ]);


            $existingUser = User::where('number', $data['number'])
                ->where('user_type', 'employee')
                ->where('user_status', 'active')
                ->first();
            if ($existingUser) {
                return response()->json([
                    'message' => 'This phone number is already in use by an active employee in another business.',
                ], 400);
            }

            $employee = User::create([
                'name'       => $data['name'],
                'email'      => $data['email'],
                'password'   => Hash::make($data['password']),
                'company_id' => $request->user()->company_id ?? null,
                'number'      => $data['number'],
                'role_id'    => $data['role_id'],
                'user_type'  => 'employee',
            ]);

            return response()->json([
                'message'  => 'Employee created successfully.',
                'employee' => new EmployeeResource($employee->load('role')),
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
     * Display the specified employee (only if user_type is 'employee').
     */
    public function show($id)
    {
        $employee = User::where('user_type', 'employee')->with(['roles.permissions', 'company'])->findOrFail($id);

        return response()->json([
            'message'   => 'Employee retrieved successfully.',
            'employee'  => new EmployeeResource($employee),
        ]);
    }

    /**
     * Update the specified employee.
     */
    public function update(Request $request, $id)
    {
        $employee = User::where('user_type', 'employee')->findOrFail($id);

        try {
            $data = $request->validate([
                'name'       => 'sometimes|string|max:255',
                'email'      => 'sometimes|string|email|max:255|unique:users,email,' . $employee->id,
                'password'   => 'sometimes|string|min:8',
                'role_id'    => 'sometimes|exists:roles,id',
                'number'     => 'sometimes|string|max:20',
            ]);

            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $employee->update($data);

            return response()->json([
                'message'  => 'Employee updated successfully.',
                'employee' => new EmployeeResource($employee->load('role')),
            ], 200);
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
     * Remove the specified employee.
     */
    public function destroy($id)
    {
        $employee = User::where('user_type', 'employee')->findOrFail($id);
        $employee->delete();

        return response()->json(['message' => 'Employee deleted successfully.']);
    }
}
