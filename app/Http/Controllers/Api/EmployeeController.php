<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserMeta;
use App\Models\CompanyUser;
use \App\Services\EmployeeCreateService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\EmployeeResource;
use Illuminate\Support\Facades\Auth;
use App\Services\UserUidService;
use App\Services\SelectedCompanyService;




class EmployeeController extends Controller
{

    protected $selectcompanyService;

    public function __construct(SelectedCompanyService $selectcompanyService)
    {
        $this->selectcompanyService = $selectcompanyService;
    }



    /**
     * Display a listing of employees.
     */
    public function index()
    {

        $employees = User::where('user_type', 'admin')
        ->with(['roles.permissions', 'companies', 'meta'])
        ->get();
        
        return response()->json([
            'message'   => 'Employees retrieved successfully.',
            'employees' => EmployeeResource::collection($employees),
            'total'     => $employees->count(),
        ], 200);
    }
    

   
    public function store(Request $request, EmployeeCreateService $userCreateService)
    {
        try {
            // Only perform request validation in the controller.
            $data = $request->validate([
                'name'         => 'required|string|max:255',
                'email'        => 'required|string|email|max:255|unique:users,email',
                'password'     => 'required|string|min:8',
                'number'       => 'required|string|max:20',
                'role'         => 'required|exists:roles,name',
                'dateOfHire'   => 'nullable|date',
                'joiningDate'  => 'nullable|date',
                'shiftTimings' => 'nullable|string'
            ]);
    
            // Delegate employee creation and association to the service.
            $employee = $userCreateService->createEmployee($data);
    
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
