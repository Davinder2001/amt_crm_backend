<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserMeta;
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

   
    
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name'         => 'required|string|max:255',
                'email'        => 'required|string|email|max:255',
                'password'     => 'required|string|min:8',
                'number'       => 'required|string|max:20',
                'role'         => 'required|exists:roles,name',
                'dateOfHire'   => 'required|nullable|date',
                'joiningDate'  => 'required|nullable|date',
                'shiftTimings' => 'required|nullable|string'
            ]);
    
            $existingUser = User::where('number', $data['number'])
                ->where('user_type', 'employee')
                ->where('user_status', 'active')
                ->first();
    
            if ($existingUser) {
                return response()->json([
                    'message' => 'This phone number is already in use by an active employee.',
                ], 400);
            }
    
            // Retrieve company id from session
            $companyId = session('selected_company.id');
    
            // Create employee with generated UID and company_id from session
            $employee = User::create([
                'name'       => $data['name'],
                'email'      => $data['email'],
                'password'   => Hash::make($data['password']),
                'company_id' => $companyId,
                'number'     => $data['number'],
                'user_type'  => 'employee',
                'uid'        => User::generateUid(),
            ]);
    
            $employee->assignRole($data['role']);
    
            // Prepare meta fields including the company id from session
            $metaFields = [
                'dateOfHire'   => $data['dateOfHire'] ?? null,
                'joiningDate'  => $data['joiningDate'] ?? null,
                'shiftTimings' => $data['shiftTimings'] ?? null,
                'company_id'   => $companyId,
            ];
    
            foreach ($metaFields as $metaKey => $metaValue) {
                if (!is_null($metaValue)) {
                    UserMeta::create([
                        'user_id'    => $employee->id,
                        'meta_key'   => $metaKey,
                        'meta_value' => $metaValue,
                    ]);
                }
            }
    
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
            $validator = Validator::make($request->all(), [
                'name'     => 'sometimes|string|max:255',
                'email'    => 'sometimes|string|email|max:255|unique:users,email,' . $employee->id,
                'password' => 'sometimes|string|min:8',
                'role'     => 'required|exists:roles,name',
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

            if (!isset($data['role'])) {
                return response()->json([
                    'message' => 'Role is required when updating an employee.',
                ], 400);
            }

            $employee->update(Arr::except($data, ['role']));
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
