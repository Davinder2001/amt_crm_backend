<?php

namespace App\Services;

use App\Models\User;
use App\Models\CompanyUser;
use App\Models\EmployeeDetail;
use App\Models\SalaryHistory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Exception;
use App\Services\SelectedCompanyService;

class EmployeeCreateService
{
    protected $selectCompanyService;

    public function __construct(SelectedCompanyService $selectCompanyService)
    {
        $this->selectCompanyService = $selectCompanyService;
    }

    /**
     * Create a new employee, associate with a company, assign a role,
     * and add employee details.
     */
    public function createEmployee(array $data)
    {
        // dd($data);
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

        EmployeeDetail::create([
            'user_id'                     => $employee->id,
            'salary'                      => $data['salary'] ?? 0,
            'dateOfHire'                => $data['dateOfHire'] ?? null,
            'joiningDate'                => $data['joiningDate'] ?? null,
            'shiftTimings'               => $data['shiftTimings'] ?? null,
            'address'                => $data['address'] ?? null,
            'nationality'                 => $data['nationality'] ?? null,
            'dob'                         => $data['dob'] ?? null,
            'religion'                    => $data['religion'] ?? null,
            'maritalStatus'              => $data['maritalStatus'] ?? null,
            'passportNo'                 => $data['passportNo'] ?? null,
            'emergencyContact'           => $data['emergencyContact'] ?? null,
            'emergencyContactRelation'  => $data['emergencyContactRelation'] ?? null,
            'currentSalary'              => $data['currentSalary'] ?? null,
            'workLocation'               => $data['workLocation'] ?? null,
            'joiningType'                => $data['joiningType'] ?? 'full-time',
            'department'                  => $data['department'] ?? null,
            'previousEmployer'           => $data['previousEmployer'] ?? null,
            'medicalInfo'                => $data['medicalInfo'] ?? null,
            'bankName'                   => $data['bankName'] ?? null,
            'accountNo'                  => $data['accountNo'] ?? null,
            'ifscCode'                   => $data['ifscCode'] ?? null,
            'panNo'                      => $data['panNo'] ?? null,
            'upiId'                      => $data['upiId'] ?? null,
            'addressProof'               => $data['addressProof'] ?? null,
            'profilePicture'             => $data['profilePicture'] ?? null,

            
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


    /**
     * Update existing employee data and handle salary change logging.
     */
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

        $employeeDetail->salary       = $data['salary'] ?? $employeeDetail->salary;
        $employeeDetail->dateOfHire   = $data['dateOfHire'] ?? $employeeDetail->dateOfHire;
        $employeeDetail->joiningDate  = $data['joiningDate'] ?? $employeeDetail->joiningDate;
        $employeeDetail->shiftTimings = $data['shiftTimings'] ?? $employeeDetail->shiftTimings;
        $employeeDetail->address = $data['address'] ?? $employeeDetail->address;
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
