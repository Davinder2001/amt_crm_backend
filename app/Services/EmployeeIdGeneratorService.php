<?php

namespace App\Services;

use App\Models\User;
use App\Models\Company;

class EmployeeIdGeneratorService
{
    /**
     * Generate a unique employee ID for a company.
     */
    public function generate(Company $company): string
    {
        $prefix = strtoupper(substr(preg_replace('/\s+/', '', $company->name), 0, 3)) . 'EM';

        $latestEmployee = User::where('employee_id', 'like', "$prefix%")
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1;

        if ($latestEmployee && preg_match('/\d+$/', $latestEmployee->employee_id, $matches)) {
            $nextNumber = intval($matches[0]) + 1;
        }

        return $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }
}
