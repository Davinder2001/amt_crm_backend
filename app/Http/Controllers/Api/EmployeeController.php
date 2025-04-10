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
use Barryvdh\DomPDF\Facade\Pdf;


class EmployeeController extends Controller
{

    /**
     * @var SelectedCompanyService
     */
    protected $selectcompanyService;

    /**
     * @var EmployeeCreateService
     */
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
    
    

    /**
     * Store employees.
     */
    public function store(Request $request, EmployeeCreateService $userCreateService)
    {
        $validator = Validator::make($request->all(), [
                'name'                      => 'required|string|min:3|max:50',
                'email'                     => 'required|email|max:100',
                'password'                  => 'required|string|min:8|max:30',
                'number'                    => 'required|string|size:10', 
                'role'                      => 'required|exists:roles,name',
                'salary'                    => 'required|numeric|min:0',
                'dateOfHire'                => 'required|date',
                'joiningDate'               => 'required|date',
                'shiftTimings'              => 'required|string|max:20',
                'address'                   => 'required|string|min:5|max:100',
                'nationality'               => 'required|string|min:3|max:30',
                'dob'                       => 'required|date',
                'religion'                  => 'required|string|min:3|max:30',
                'maritalStatus'             => 'required|string|max:20', 
                'passportNo'                => 'required|string|size:8', 
                'emergencyContact'          => 'required|string|size:10',
                'emergencyContactRelation'  => 'required|string|min:3|max:30',
                'currentSalary'             => 'required|numeric|min:0',
                'workLocation'              => 'required|string|min:3|max:100',
                'joiningType'               => 'required|in:full-time,part-time,contract',
                'department'                => 'required|string|min:2|max:50',
                'previousEmployer'          => 'required|string|min:3|max:50',
                'medicalInfo'               => 'required|string|min:3|max:100',
                'bankName'                  => 'required|string|min:2|max:50',
                'accountNo'                 => 'required|string|min:9|max:18', 
                'ifscCode'                  => 'required|string|size:11', 
                'panNo'                     => 'required|string|size:10', 
                'upiId'                     => 'required|string|min:8|max:50',
                'addressProof'              => 'required|string|min:5|max:50', 
                'profilePicture'            => 'required|string|max:255',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $employee = $userCreateService->createEmployee($validator->validated());

            return response()->json([
                'message'  => 'Employee created successfully.',
                'employee' => new EmployeeResource($employee->load('roles', 'employeeDetail')),
            ], 201);

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

   /**
     * Generate salary slip for the specified employee.
     */
    public function salarySlip($id)
    {
        $employee = User::where('user_type', 'employee')->with(['roles.permissions', 'companies', 'meta'])->find($id); 
    
        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Employee not found.'
            ], 404);
        }
    
        $pdf = Pdf::loadView('pdf.salary-slip', ['employee' => $employee]);
        $pdfContent = $pdf->output();
    
        $pdfBase64 = base64_encode($pdfContent);
    
        return response()->json([
            'status' => true,
            'message' => 'Employee retrieved successfully.',
            'employee' => new SalaryResource($employee),
            'pdf_base64' => $pdfBase64,
            'file_name' => 'salary-slip-' . $employee->id . '.pdf',
        ], 200);
    }
}
