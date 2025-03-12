<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SuperAdminSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->insert([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'number' => '7894561230',
            'user_type' => 'super-admin',
            'password' => Hash::make('superadminpassword'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
