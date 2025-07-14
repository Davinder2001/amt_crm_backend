<?php

namespace App\Services;

use App\Models\User;
use App\Models\CompanyUser;
use App\Models\EmployeeDetail;
use App\Models\SalaryHistory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\File;
use App\Services\SelectedCompanyService;
use Exception;

class EmployeeCreateService
{
    protected $selectCompanyService;

    public function __construct(SelectedCompanyService $selectCompanyService)
    {
        $this->selectCompanyService = $selectCompanyService;
    }

    public function createEmployee(array $data)
    {
        $existingUser = User::where('number', $data['number'])
            ->whereIn('user_type', ['employee', 'admin'])
            ->where('user_status', 'active')
            ->first();

        if ($existingUser) {
            throw ValidationException::withMessages([
                'number' => ['This phone number is already in use by an active employee.']
            ]);
        }

        $company = $this->selectCompanyService->getSelectedCompanyOrFail();
        if (!$company) {
            throw new Exception('No associated company found for the authenticated user.');
        }

        $employee = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'number'    => $data['number'],
            'user_type' => 'employee',
        ]);

        CompanyUser::create([
            'user_id'    => $employee->id,
            'company_id' => $company->company_id,
            'user_type'  => 'staff',
            'status'     => 1,
        ]);

        $employee->assignRole($data['role']);

        // ✅ Profile picture upload
        $profilePicturePath = null;
        if (isset($data['profilePicture']) && $data['profilePicture']->isValid()) {
            $filename = time() . '_' . uniqid() . '.' . $data['profilePicture']->getClientOriginalExtension();
            $destinationPath = public_path('images/employeeprofiles');
            File::ensureDirectoryExists($destinationPath);
            $data['profilePicture']->move($destinationPath, $filename);
            $profilePicturePath = 'images/employeeprofiles/' . $filename;
        }

        EmployeeDetail::create([
            'user_id'                  => $employee->id,
            'salary'                   => $data['salary'] ?? 0,
            'dateOfHire'               => $data['dateOfHire'] ?? null,
            'joiningDate'              => $data['joiningDate'] ?? null,
            'shift_id'                 => $data['shiftTimings'] ?? null,
            'address'                  => $data['address'] ?? null,
            'nationality'              => $data['nationality'] ?? null,
            'dob'                      => $data['dob'] ?? null,
            'religion'                 => $data['religion'] ?? null,
            'maritalStatus'            => $data['maritalStatus'] ?? null,
            'idProofType'              => $data['idProofType'] ?? null,
            'id_proof_type'            => $data['id_proof_type'] ?? null,
            'idProofValue'             => $data['idProofValue'] ?? null,
            'emergencyContact'         => $data['emergencyContact'] ?? null,
            'emergencyContactRelation' => $data['emergencyContactRelation'] ?? null,
            'workLocation'             => $data['workLocation'] ?? null,
            'joiningType'              => $data['joiningType'] ?? 'full-time',
            'department'               => $data['department'] ?? null,
            'previousEmployer'         => $data['previousEmployer'] ?? null,
            'bankName'                 => $data['bankName'] ?? null,
            'acc_hol_name'             => $data['acc_hol_name'] ?? null,
            'accountNo'                => $data['accountNo'] ?? null,
            'ifscCode'                 => $data['ifscCode'] ?? null,
            'panNo'                    => $data['panNo'] ?? null,
            'upiId'                    => $data['upiId'] ?? null,
            'addressProof'             => $data['addressProof'] ?? null,
            'profilePicture'           => $profilePicturePath,
        ]);

        SalaryHistory::create([
            'user_id'         => $employee->id,
            'previous_salary' => 0,
            'new_salary'      => $data['salary'],
            'increment_date'  => now(),
            'reason'          => 'Initial Salary',
        ]);

        return $employee;
    }

    public function updateEmployee(User $employee, array $data)
    {
        $employee->update([
            'name'   => $data['name'] ?? $employee->name,
            'email'  => $data['email'] ?? $employee->email,
            'number' => $data['number'] ?? $employee->number,
        ]);

        if (isset($data['password'])) {
            $employee->update([
                'password' => Hash::make($data['password']),
            ]);
        }

        if (isset($data['role'])) {
            $employee->syncRoles($data['role']);
        }

        $employeeDetail = EmployeeDetail::firstOrNew(['user_id' => $employee->id]);
        $previousSalary = $employeeDetail->salary;

        $employeeDetail->fill([
            'salary'                   => $data['salary'] ?? $employeeDetail->salary,
            'dateOfHire'               => $data['dateOfHire'] ?? $employeeDetail->dateOfHire,
            'joiningDate'              => $data['joiningDate'] ?? $employeeDetail->joiningDate,
            'shift_id'                 => $data['shiftTimings'] ?? $employeeDetail->shift_id,
            'address'                  => $data['address'] ?? $employeeDetail->address,
            'nationality'              => $data['nationality'] ?? $employeeDetail->nationality,
            'dob'                      => $data['dob'] ?? $employeeDetail->dob,
            'religion'                 => $data['religion'] ?? $employeeDetail->religion,
            'maritalStatus'            => $data['maritalStatus'] ?? $employeeDetail->maritalStatus,
            'idProofType'              => $data['idProofType'] ?? $employeeDetail->idProofType,
            'idProofValue'             => $data['idProofValue'] ?? $employeeDetail->idProofValue,
            'emergencyContact'         => $data['emergencyContact'] ?? $employeeDetail->emergencyContact,
            'emergencyContactRelation' => $data['emergencyContactRelation'] ?? $employeeDetail->emergencyContactRelation,
            'workLocation'             => $data['workLocation'] ?? $employeeDetail->workLocation,
            'joiningType'              => $data['joiningType'] ?? $employeeDetail->joiningType,
            'department'               => $data['department'] ?? $employeeDetail->department,
            'previousEmployer'         => $data['previousEmployer'] ?? $employeeDetail->previousEmployer,
            'bankName'                 => $data['bankName'] ?? $employeeDetail->bankName,
            'acc_hol_name'             => $data['acc_hol_name'] ?? $employeeDetail->acc_hol_name,
            'accountNo'                => $data['accountNo'] ?? $employeeDetail->accountNo,
            'ifscCode'                 => $data['ifscCode'] ?? $employeeDetail->ifscCode,
            'panNo'                    => $data['panNo'] ?? $employeeDetail->panNo,
            'upiId'                    => $data['upiId'] ?? $employeeDetail->upiId,
            'addressProof'             => $data['addressProof'] ?? $employeeDetail->addressProof,
        ]);

        $employeeDetail->save();

        if (isset($data['salary']) && $data['salary'] != $previousSalary) {
            SalaryHistory::create([
                'user_id'         => $employee->id,
                'previous_salary' => $previousSalary,
                'new_salary'      => $data['salary'],
                'increment_date'  => now(),
                'reason'          => $data['reason'] ?? 'Salary Updated',
            ]);
        }

        return $employee;
    }
}
