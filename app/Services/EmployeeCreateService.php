<?php

namespace App\Services;

use App\Models\User;
use App\Models\CompanyUser;
use App\Models\UserMeta;
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
     * Create a new employee, associate with a company,
     * assign a role, and add additional meta data.
     *
     * @param array $data
     * @return \App\Models\User
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function createEmployee(array $data)
    {
        // Check for duplicate employee based on phone number.
        $existingUser = User::where('number', $data['number'])
            ->where('user_type', 'employee')
            ->where('user_status', 'active')
            ->first();

        if ($existingUser) {
            throw ValidationException::withMessages([
                'number' => ['This phone number is already in use by an active employee.']
            ]);
        }

        // Retrieve the company associated with the authenticated user.
        $company = $this->selectCompanyService->getSelectedCompanyOrFail();
        if (!$company) {
            throw new Exception('No associated company found for the authenticated user.');
        }

        // Create the employee record.
        $employee = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'number'    => $data['number'],
            'user_type' => 'employee',
        ]);

        // Associate the employee with the company using the pivot table.
        CompanyUser::create([
            'user_id'    => $employee->id,
            'company_id' => $company->id,
            'user_type'  => 'staff',
            'status'     => 1,
        ]);

        // Assign the specified role.
        $employee->assignRole($data['role']);

        // Save additional meta data.
        $metaFields = [
            'dateOfHire'   => $data['dateOfHire']   ?? null,
            'joiningDate'  => $data['joiningDate']  ?? null,
            'shiftTimings' => $data['shiftTimings'] ?? null,
        ];

        foreach ($metaFields as $metaKey => $metaValue) {
            if (!is_null($metaValue)) {
                UserMeta::create([
                    'user_id'    => $employee->id,
                    'meta_key'   => $metaKey,
                    'meta_value' => $metaValue,
                ]);
            }
        }

        return $employee;
    }
}
