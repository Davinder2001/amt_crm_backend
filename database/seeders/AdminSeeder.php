<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['company_id' => 'AMTCOM0000002'],
            [
                'company_name' => 'Demo Admin Company',
                'company_slug' => 'demo-admin-company',
                'payment_status'      => 'completed',
                'verification_status' => 'verified',
            ]
        );

        $admin = User::factory()->create([
            'name'       => 'Admin',
            'email'      => 'admin@admin.com',
            'number'     => '9876543210',
            'user_type'     => 'admin',
            'uid'        => 'AMT0000000002',
            'password'  => Hash::make('adminpassword'),
        ]);

        DB::table('company_user')->insert([
            'user_id'    => $admin->id,
            'company_id' => $company->id,
            'user_type'  => 'admin', 
            'status'     => '1',
        ]);

        $role = Role::updateOrCreate(
            ['name' => 'admin', 'guard_name' => 'web'],
            ['company_id' => $company->id]
        );

        $admin->assignRole($role);
    }
}
