<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserMeta;
use \App\Services\EmployeeCreateService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Models\Package;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\EmployeeResource;
use App\Services\SelectedCompanyService;



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
        $userType   = $request->user_type;
        $employees  = User::where('user_type', $userType)->with(['roles.permissions', 'companies', 'employeeDetail.shift'])->get();

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
            'number'                    => 'required|numeric|size:10',
            'role'                      => 'required|exists:roles,name',
            'salary'                    => 'required|numeric|min:0',
            'dateOfHire'                => 'required|date',
            'joiningDate'               => 'required|date',
            'shiftTimings'              => 'nullable|integer|exists:shifts,id',
            'address'                   => 'required|text',
            'nationality'               => 'required|string|min:3|max:30',
            'dob'                       => 'required|date',
            'religion'                  => 'required|string|min:3|max:30',
            'maritalStatus'             => 'required|string|max:20',
            'idProofType'               => 'nullable|string',
            'idProofValue'              => 'nullable|string',
            'emergencyContact'          => 'required|numeric|size:10',
            'emergencyContactRelation'  => 'required|numeric|min:3|max:30',
            'currentSalary'             => 'required|numeric|min:0',
            'workLocation'              => 'required|string|min:3|max:100',
            'joiningType'               => 'required|in:full-time,part-time,contract',
            'department'                => 'required|string|min:2|max:50',
            'previousEmployer'          => 'required|string|min:3|max:50',
            'acc_hol_name'              => 'required|string|min:3|max:100',
            'bankName'                  => 'required|string|min:2|max:50',
            'accountNo'                 => 'required|numeric|min:9|max:18',
            'ifscCode'                  => 'required|string|size:11',
            'panNo'                     => 'required|string|size:10',
            'upiId'                     => 'required|string|min:8|max:50',
            'addressProof'              => 'required|string|min:5|max:50',
            'id_proof_type'             => 'nullable|string|min:5|max:50',
            'profilePicture'            => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $selectedCompany = SelectedCompanyService::getSelectedCompanyOrFail();
        $packageId       = $selectedCompany->company->package_id ?? 1;
        $package         = Package::findOrFail($packageId);

        $packageType           = $package->package_type;
        $allowedEmployeeCount  = $package->employee_limit;
        $now                   = now();

        $employeeQuery = User::whereHas('roles', fn($q) => $q->where('name', '!=', 'admin'))
            ->where('company_id', $selectedCompany->id);

        if ($packageType === 'yearly') {
            $employeeQuery->whereYear('created_at', $now->year);
        } elseif ($packageType === 'three-years') {
            $employeeQuery->whereBetween('created_at', [
                $now->copy()->subYears(3)->startOfDay(),
                $now->copy()->endOfDay(),
            ]);
        }

        $currentEmployeeCount = $employeeQuery->count();

        if ($currentEmployeeCount >= $allowedEmployeeCount) {
            return response()->json([
                'message' => "You have reached your {$packageType} employee limit of {$allowedEmployeeCount}.",
            ], 403);
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
    public function show(Request $request, $id)
    {
        $userType = $request->user_type;
        $employee = User::where('user_type', $userType)->where('id', $id)->with(['roles.permissions', 'companies', 'employeeDetail'])->first();

        if (!$employee) {
            return response()->json([
                'message' => 'Employee not found.',
            ], 404);
        }

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
                'name'                      => 'sometimes|string|min:3|max:50',
                'email'                     => 'sometimes|email|max:100',
                'password'                  => 'sometimes|string|min:8|max:30',
                'number'                    => 'sometimes|numeric|size:10',
                'role'                      => 'sometimes|exists:roles,name',
                'salary'                    => 'sometimes|numeric|min:0',
                'dateOfHire'                => 'sometimes|date',
                'joiningDate'               => 'sometimes|date',
                'shiftTimings'              => 'nullable|string|max:20',
                'address'                   => 'sometimes|string|min:5|max:100',
                'nationality'               => 'sometimes|string|min:3|max:30',
                'dob'                       => 'sometimes|date',
                'religion'                  => 'sometimes|string|min:3|max:30',
                'maritalStatus'             => 'sometimes|string|max:20',
                'idProofType'               => 'nullable|string',
                'idProofValue'              => 'nullable|string',
                'emergencyContact'          => 'sometimes|numeric|size:10',
                'emergencyContactRelation'  => 'sometimes|numeric|min:3|max:30',
                'workLocation'              => 'sometimes|string|min:3|max:100',
                'joiningType'               => 'sometimes|in:full-time,part-time,contract',
                'department'                => 'sometimes|string|min:2|max:50',
                'previousEmployer'          => 'sometimes|string|min:3|max:50',
                'acc_hol_name'              => 'sometimes|string|min:3|max:100',
                'bankName'                  => 'sometimes|string|min:2|max:50',
                'accountNo'                 => 'sometimes|numeric|min:9|max:18',
                'ifscCode'                  => 'sometimes|string|size:11',
                'panNo'                     => 'sometimes|string|size:10',
                'upiId'                     => 'sometimes|string|min:8|max:50',
                'addressProof'              => 'sometimes|string|min:5|max:50',
                'profilePicture'            => 'sometimes|file|image|mimes:jpeg,png,jpg,webp|max:2048',
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
                        ['user_id'      => $employee->id, 'meta_key' => $metaKey],
                        ['meta_value'   => $metaValue]
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
     * Change employee status.
     */
    public function changeEmpStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive,blocked',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $status     = $request->input('status');
        $employee   = User::where('user_type', 'employee')->findOrFail($id);
        $employee->user_status = $status;
        $employee->save();

        return response()->json([
            'message' => 'Employee status updated successfully.',
            'employee' => $employee,
        ]);
    }
}
