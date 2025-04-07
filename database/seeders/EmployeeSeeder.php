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
        // Get company by its UID (company_code)
        $company = Company::where('company_id', 'AMTCOM0000002')->first();

        if (!$company) {
            throw new \Exception('Company with ID AMTCOM0000002 not found');
        }

        // Create or get the employee role for that company
        $role = Role::firstOrCreate([
            'name' => 'employee',
            'guard_name' => 'web',
            'company_id' => $company->id,
        ]);

        // Create 5 employees
        for ($i = 1; $i <= 5; $i++) {
            $employee = User::create([
                'name'      => "Employee $i",
                'email'     => "employee$i@example.com",
                'number'    => "900000000$i",
                'password'  => Hash::make('password'),
                'user_type' => 'employee',
            ]);

            CompanyUser::create([
                'user_id'    => $employee->id,
                'company_id' => $company->id,
                'user_type'  => 'staff',
                'status'     => 1,
            ]);

            $employee->assignRole($role);

            // Create employee detail
            EmployeeDetail::create([
                'user_id'                  => $employee->id,
                'salary'                   => 25000 + ($i * 1000),
                'dateOfHire'               => now()->subDays(30),
                'joiningDate'              => now()->subDays(20),
                'shiftTimings'             => '9am - 6pm',
                'address'                  => "Employee $i Street",
                'nationality'              => 'Indian',
                'dob'                      => '1995-01-01',
                'religion'                 => 'Hindu',
                'maritalStatus'            => 'Single',
                'passportNo'               => "PASS$i",
                'emergencyContact'         => "99999999$i",
                'emergencyContactRelation' => 'Father',
                'currentSalary'            => '26000',
                'workLocation'             => 'Head Office',
                'joiningType'              => 'full-time',
                'department'               => 'IT',
                'previousEmployer'         => 'ABC Corp',
                'medicalInfo'              => 'None',
                'bankName'                 => 'SBI',
                'accountNo'                => '1234567890' . $i,
                'ifscCode'                 => 'SBIN0000123',
                'panNo'                    => 'ABCDE1234F',
                'upiId'                    => 'employee' . $i . '@upi',
                'addressProof'             => 'Aadhar',
                'profilePicture'           => null,
            ]);
        }
    }
}
