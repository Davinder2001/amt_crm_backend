<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserMeta;
use \App\Services\EmployeeCreateService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\SalaryResource;
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
    public function index(Request $request)
    {
        $userType = $request->user_type; 
        $employees = User::where('user_type', $userType)->with(['roles.permissions', 'companies', 'employeeDetail'])->get();
    
        return response()->json([
            'message'   => 'Employees retrieved successfully.',
            'employees' => EmployeeResource::collection($employees),
            'total'     => $employees->count(),
        ], 200);
    }
    
    

    public function store(Request $request, EmployeeCreateService $userCreateService)
    {
        try {
            $data = $request->validate([
                'name'                      => 'required|string|max:255',
                'email'                     => 'required|string|email|max:255|unique:users,email',
                'password'                  => 'required|string|min:8',
                'number'                    => 'required|string|max:20|unique:users,number',
                'role'                      => 'required|exists:roles,name',
                'salary'                    => 'required|numeric|min:0',
                'dateOfHire'                => 'nullable|date',
                'joiningDate'               => 'nullable|date',
                'shiftTimings'              => 'nullable|string|max:255',
                'address'                   => 'nullable|string|max:500',
                'nationality'               => 'nullable|string|max:100',
                'dob'                       => 'nullable|date',
                'religion'                  => 'nullable|string|max:100',
                'maritalStatus'             => 'nullable|string|max:100',
                'passportNo'                => 'nullable|string|max:50|unique:employee_details,passportNo',
                'emergencyContact'          => 'nullable|string|max:20',
                'emergencyContactRelation'  => 'nullable|string|max:50',
                'currentSalary'             => 'nullable|numeric|min:0',
                'workLocation'              => 'nullable|string|max:255',
                'joiningType'               => 'required|in:full-time,part-time,contract',
                'department'                => 'nullable|string|max:255',
                'previousEmployer'          => 'nullable|string|max:255',
                'medicalInfo'               => 'nullable|string|max:500',
                'bankName'                  => 'nullable|string|max:255',
                'accountNo'                 => 'nullable|string|max:50|unique:employee_details,accountNo',
                'ifscCode'                  => 'nullable|string|max:20',
                'panNo'                     => 'nullable|string|max:20|unique:employee_details,panNo',
                'upiId'                     => 'nullable|string|max:50',
                'addressProof'              => 'nullable|string|max:255',
                'profilePicture'            => 'nullable|string|max:255',
            ]);
    

            $employee = $userCreateService->createEmployee($data);
    
            return response()->json([
                'message'  => 'Employee created successfully.',
                'employee' => new EmployeeResource($employee->load('roles', 'employeeDetail')),
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
                'name'         => 'sometimes|string|max:255',
                'email'        => 'sometimes|string|email|max:255',
                'password'     => 'sometimes|string|min:8',
                'role'         => 'required|exists:roles,name',
                'number'       => 'sometimes|string|max:20',
                'salary'       => 'sometimes|numeric|min:0',
                'dateOfHire'   => 'sometimes|date',
                'joiningDate'  => 'sometimes|date',
                'shiftTimings' => 'sometimes|string',
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
    
            $employee->update(Arr::except($data, ['role', 'salary', 'dateOfHire', 'joiningDate', 'shiftTimings']));
    
            $employee->syncRoles($data['role']);
            $metaFields = [
                'salary'       => $data['salary']       ?? null,
                'dateOfHire'   => $data['dateOfHire']   ?? null,
                'joiningDate'  => $data['joiningDate']  ?? null,
                'shiftTimings' => $data['shiftTimings'] ?? null,
            ];
    
            foreach ($metaFields as $metaKey => $metaValue) {
                if (!is_null($metaValue)) {
                    UserMeta::updateOrCreate(
                        ['user_id' => $employee->id, 'meta_key' => $metaKey],
                        ['meta_value' => $metaValue]
                    );
                }
            }
    
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


    public function salarySlip($id)
    {

        $employee = User::where('user_type', 'employee')
            ->with(['roles.permissions', 'companies', 'meta'])
            ->find($id); 

        if (!$employee) {
            return response()->json([
                'message' => 'Employee not found.'
            ], 404);
        }
    
        return response()->json([
            'message'   => 'Employee retrieved successfully.',
            'employee'  => new SalaryResource($employee),
        ], 200);
    }


    // public function downloadSalarySlipPdf($id)
    // {

    //     $employee = User::where('user_type', 'employee')
    //         ->with(['roles.permissions', 'companies', 'meta'])
    //         ->find($id); 

    //     if (!$employee) {
    //         return response()->json([
    //             'message' => 'Employee not found.'
    //         ], 404);
    //     }

    //     $pdfData = [
    //         'employee' => $employee,
    //         'salaryDetails' => $employee->salaryDetails(), 
    //     ];

    //     $pdf = PDF::loadView('pdf.salary-slip', $pdfData);

    //     return $pdf->download('salary-slip-' . $employee->id . '.pdf');
    // }

    

}
