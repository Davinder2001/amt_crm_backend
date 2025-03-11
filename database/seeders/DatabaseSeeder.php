<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // You can create a user with a specific company_id (2001)
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'number' => '7018616800',
            'company_id' => '1',
            'password' => Hash::make('Password'), // Hash the password
            'company_id' => 2001, // Set the company_id to 2001
        ]);
    }
}
