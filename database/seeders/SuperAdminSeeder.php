<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run()
    {

        Role::firstOrCreate(['name' => 'super-admin']);

        $user = User::create([
            'name'      => 'Super Admin',
            'email'     => 'superadmin@example.com',
            'number'    => '7894561230',
            'uid'       => '00000001',
            'user_type' => 'super-admin',
            'password'  => Hash::make('superadminpassword'),
        ]);

        $user->assignRole('super-admin');
    }
}
