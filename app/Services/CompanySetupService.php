<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\Tax;
use App\Models\Role;

class CompanySetupService
{
    public static function setupDefaults($company, $user = null)
    {
        $tax = (new Tax)->newInstance([
            'company_id' => $company->id,
            'name'       => 'GST',
            'rate'       => 18,
        ]);
        $tax->saveQuietly();

        $shift = (new Shift)->newInstance([
            'company_id'     => $company->id,
            'shift_name'     => 'General Shift',
            'start_time'     => '09:00:00',
            'end_time'       => '18:00:00',
            'weekly_off_day' => 'Sunday',
        ]);
        $shift->saveQuietly();

        $roles = ['admin', 'employee', 'hr', 'supervisor', 'sales'];
        foreach ($roles as $roleName) {
            $existing = Role::withoutGlobalScopes()->where([
                'name'       => $roleName,
                'guard_name' => 'web',
                'company_id' => $company->id,
            ])->first();

            if (!$existing) {
                $role = (new Role)->newInstance([
                    'name'       => $roleName,
                    'guard_name' => 'web',
                    'company_id' => $company->id,
                ]);
                $role->saveQuietly();
            }
        }

        if ($user) {
            $adminRole = Role::withoutGlobalScopes()->where([
                'name'       => 'admin',
                'guard_name' => 'web',
                'company_id' => $company->id,
            ])->first();

            if ($adminRole) {
                $user->assignRole($adminRole);
            }
        }
    }
}
