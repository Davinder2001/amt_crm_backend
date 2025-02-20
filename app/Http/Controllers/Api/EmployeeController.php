<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Exception;

class EmployeeController extends Controller
{
    /**
     * Display a list of all employees.
     */
    public function index()
    {
        $employees = EmployeeResource::collection(Employee::all());

        return response()->json([
            'message' => 'Employees retrieved successfully.',
            'employees' => $employees,
            'length' => $employees->count(),
        ], 200);
    }



    /**
     * Store a newly created employee in storage.
     */
    public function store(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|unique:employees',
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:employees',
                'mobile_no' => 'required|unique:employees',
                'password' => 'required|min:6|confirmed',
                'admin_id' => 'required',
                'role_id' => 'required',
                'aadhar_card_no' => 'required|unique:employees'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $employee = Employee::create([
                'employee_id' => $request->employee_id,
                'name' => $request->name,
                'email' => $request->email,
                'mobile_no' => $request->mobile_no,
                'password' => Hash::make($request->password),
                'admin_id' => $request->admin_id,
                'role_id' => $request->role_id,
                'aadhar_card_no' => $request->aadhar_card_no,
            ]);

            return response()->json([
                'message' => 'Employee created successfully!',
                'employee' => new EmployeeResource($employee),
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Display the specified employee.
     */
    public function show($id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        return new EmployeeResource($employee);
    }



    /**
     * Update the specified employee in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $employee = Employee::find($id);

            if (!$employee) {
                return response()->json(['message' => 'Employee not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:employees,email,' . $employee->id,
                'mobile_no' => 'sometimes|unique:employees,mobile_no,' . $employee->id,
                'password' => 'sometimes|min:6|confirmed',
                'aadhar_card_no' => 'sometimes|unique:employees,aadhar_card_no,' . $employee->id,
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $employee->update([
                'name' => $request->name ?? $employee->name,
                'email' => $request->email ?? $employee->email,
                'mobile_no' => $request->mobile_no ?? $employee->mobile_no,
                'password' => $request->password ? Hash::make($request->password) : $employee->password,
                'aadhar_card_no' => $request->aadhar_card_no ?? $employee->aadhar_card_no,
            ]);

            return response()->json([
                'message' => 'Employee updated successfully!',
                'employee' => new EmployeeResource($employee),
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Remove the specified employee from storage.
     */
    public function destroy($id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        $employee->delete();

        return response()->json(['message' => 'Employee deleted successfully!'], 200);
    }
}
