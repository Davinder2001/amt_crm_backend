<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Role;
use App\Models\EmployeeDetail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $companyIds = [
            'AMTCOM0000001',
            'AMTCOM0000002',
            'AMTCOM0000003',
            'AMTCOM0000004',
            'AMTCOM0000005',
        ];

        $employeeCounter = 1;

        foreach ($companyIds as $companyId) {
            $company = Company::where('company_id', $companyId)->first();

            if (!$company) {
                throw new \Exception("Company with ID $companyId not found");
            }

            $role = Role::firstOrCreate([
                'name'       => 'employee',
                'guard_name' => 'web',
                'company_id' => $company->id,
            ]);

            for ($i = 1; $i <= 2; $i++) {
                $employeeNumber = '8' . str_pad((string)$employeeCounter, 9, '0', STR_PAD_LEFT);

                $employee = User::create([
                    'name'      => "Employee $employeeCounter",
                    'email'     => "employee$employeeCounter@example.com",
                    'number'    => $employeeNumber,
                    'password'  => Hash::make('password'),
                    'user_type' => 'employee',
                    'uid'       => 'AMT00000000' . ($employeeCounter + 4),
                ]);

                CompanyUser::create([
                    'user_id'    => $employee->id,
                    'company_id' => $company->id,
                    'user_type'  => 'staff',
                    'status'     => 1,
                ]);

                $employee->assignRole($role);

                $salary = 25000 + ($employeeCounter * 1000);

                EmployeeDetail::create([
                    'user_id'                  => $employee->id,
                    'salary'                   => $salary,
                    'dateOfHire'               => now()->subDays(30),
                    'joiningDate'              => now()->subDays(20),
                    'shift_id'                 => null,
                    'address'                  => "Employee $employeeCounter Street, City, State, Country",
                    'nationality'              => 'Indian',
                    'dob'                      => '1995-01-01',
                    'religion'                 => 'Hindu',
                    'maritalStatus'            => 'Single',
                    'idProofType'              => 'Aadhar',
                    'id_proof_type'            => 'Aadhar',
                    'idProofValue'             => '1234-5678-9012',
                    'emergencyContact'         => 9999999900 + $employeeCounter,
                    'emergencyContactRelation' => 'uknown', 
                    'workLocation'             => 'Head Office',
                    'joiningType'              => 'full-time',
                    'department'               => 'IT',
                    'previousEmployer'         => 'ABC Corp',
                    'acc_hol_name'             => 'Employee Holder ' . $employeeCounter,
                    'bankName'                 => 'SBI',
                    'accountNo'                => 1234567890 + $employeeCounter,
                    'ifscCode'                 => 'SBIN0000123',
                    'panNo'                    => 'ABCDE1234F',
                    'upiId'                    => 'employee' . $employeeCounter . '@upi',
                    'addressProof'             => 'Aadhar',
                    'profilePicture'           => null,
                ]);

                $employeeCounter++;
            }
        }
    }
}
