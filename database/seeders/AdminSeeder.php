<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run()
    {
        // Create the admin user with specific values
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'number' => '7018616800',
            'uid'     => '00000002',
            'company_id' => 2001, // Set company_id to 2001 for admin
            'password' => Hash::make('Password'), // Hash the password for security
        ]);
    }
}
