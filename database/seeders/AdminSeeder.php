<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use App\Models\Role;
use App\Models\Package;
use App\Models\BusinessCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            [
                'company_id'   => 'AMTCOM0000002',
                'company_name' => 'Demo Admin Company One',
                'company_slug' => 'demo-admin-company-one',
                'package_name' => 'Basic',
            ],
            [
                'company_id'   => 'AMTCOM0000003',
                'company_name' => 'Demo Admin Company Two',
                'company_slug' => 'demo-admin-company-two',
                'package_name' => 'Standard',
            ],
            [
                'company_id'   => 'AMTCOM0000004',
                'company_name' => 'Demo Admin Company Three',
                'company_slug' => 'demo-admin-company-three',
                'package_name' => 'Premium',
            ],
            [
                'company_id'   => 'AMTCOM0000005',
                'company_name' => 'Demo Admin Company Four',
                'company_slug' => 'demo-admin-company-four',
                'package_name' => 'Basic',
            ],
            [
                'company_id'   => 'AMTCOM0000006',
                'company_name' => 'Demo Admin Company Five',
                'company_slug' => 'demo-admin-company-five',
                'package_name' => 'Standard',
            ],
        ];

        $admins = [
            [
                'name'     => 'Admin One',
                'email'    => 'admin1@example.com',
                'number'   => '9000000001',
                'uid'      => 'AMT0000000002',
                'password' => Hash::make('password1'),
            ],
            [
                'name'     => 'Admin Two',
                'email'    => 'admin2@example.com',
                'number'   => '9000000002',
                'uid'      => 'AMT0000000003',
                'password' => Hash::make('password2'),
            ],
            [
                'name'     => 'Admin Three',
                'email'    => 'admin3@example.com',
                'number'   => '9000000003',
                'uid'      => 'AMT0000000004',
                'password' => Hash::make('password3'),
            ],
            [
                'name'     => 'Admin Four',
                'email'    => 'admin4@example.com',
                'number'   => '9000000004',
                'uid'      => 'AMT0000000005',
                'password' => Hash::make('password4'),
            ],
            [
                'name'     => 'Admin Five',
                'email'    => 'admin5@example.com',
                'number'   => '9000000005',
                'uid'      => 'AMT0000000006',
                'password' => Hash::make('password5'),
            ],
        ];

        foreach ($companies as $index => $companyData) {
            $package = Package::where('name', $companyData['package_name'])->first();
            $businessCategory = $package->businessCategories()->first();

            $company = Company::firstOrCreate(
                ['company_id' => $companyData['company_id']],
                [
                    'company_name'          => $companyData['company_name'],
                    'company_slug'          => $companyData['company_slug'],
                    'package_id'            => $package?->id,
                    'business_category'     => $businessCategory?->id,
                    'payment_status'        => 'completed',
                    'verification_status'   => 'verified',
                ]
            );

            $role = Role::updateOrCreate(
                ['name' => 'admin', 'guard_name' => 'web', 'company_id' => $company->id]
            );

            $adminData  = $admins[$index];
            $admin      = User::factory()->create(array_merge($adminData, [
                'user_type' => 'admin',
            ]));

            DB::table('company_user')->insert([
                'user_id'    => $admin->id,
                'company_id' => $company->id,
                'user_type'  => 'admin',
                'status'     => '1',
            ]);

            $admin->assignRole($role);
        }
    }
}
