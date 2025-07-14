<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Company;
use App\Models\CompanyUser;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run()
    {
        $defaultCompanyId = 'AMTCOM0000000';

        $role = Role::updateOrCreate(
            ['name' => 'super-admin', 'guard_name' => 'web'],
            ['company_id' => $defaultCompanyId] 
        );

        $user = User::updateOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name'      => 'Super Admin',
                'number'    => '7894561230',
                'uid'       => 'AMT0000000000',
                'user_type' => 'super-admin',
                'password'  => Hash::make('superadminpassword'),
            ]
        );

        $user->assignRole($role);
    }
}
