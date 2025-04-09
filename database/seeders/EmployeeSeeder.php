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
            'AMTCOM0000002',
            'AMTCOM0000003',
            'AMTCOM0000004',
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
                $employeeNumber = '800000000' . $employeeCounter;

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

                EmployeeDetail::create([
                    'user_id'                  => $employee->id,
                    'salary'                   => 25000 + ($employeeCounter * 1000),
                    'dateOfHire'               => now()->subDays(30),
                    'joiningDate'              => now()->subDays(20),
                    'shiftTimings'             => '9am - 6pm',
                    'address'                  => "Employee $employeeCounter Street",
                    'nationality'              => 'Indian',
                    'dob'                      => '1995-01-01',
                    'religion'                 => 'Hindu',
                    'maritalStatus'            => 'Single',
                    'passportNo'               => "PASS$employeeCounter",
                    'emergencyContact'         => "99999999$employeeCounter",
                    'emergencyContactRelation' => 'Father',
                    'currentSalary'            => 26000 + ($employeeCounter * 500),
                    'workLocation'             => 'Head Office',
                    'joiningType'              => 'full-time',
                    'department'               => 'IT',
                    'previousEmployer'         => 'ABC Corp',
                    'medicalInfo'              => 'None',
                    'bankName'                 => 'SBI',
                    'accountNo'                => '1234567890' . $employeeCounter,
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
