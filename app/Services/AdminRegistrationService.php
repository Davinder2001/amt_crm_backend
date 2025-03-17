<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AdminRegistrationService
{
    public function register(array $data)
    {
        $slug = Str::slug($data['company_name']);
        if (Company::where('company_slug', $slug)->exists()) {
            throw ValidationException::withMessages([
                'company_name' => ['Company slug already exists. Please choose a different company name.']
            ]);
        }
        
        return DB::transaction(function () use ($data, $slug) {
            // Create the company first.
            $company = Company::create([
                'company_id'          => Company::generateCompanyId(),
                'company_name'        => $data['company_name'],
                'company_slug'        => $slug,
                'payment_status'      => 'pending',
                'verification_status' => 'pending',
            ]);

            // Generate a unique UID for the new user.
            $lastUser = User::orderBy('id', 'desc')->first();
            if ($lastUser && $lastUser->uid) {
                $lastNumber = (int) substr($lastUser->uid, 3);
                $newNumber  = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }
            $uid = 'AMT' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);

            // Create the admin user with the generated UID.
            $user = User::create([
                'uid'        => $uid,
                'name'       => $data['name'],
                'email'      => $data['email'],
                'password'   => Hash::make($data['password']),
                'role'       => 'admin',
                'number'     => $data['number'],
                'company_id' => $company->id,
            ]);

            // Insert a new role for this company.
            DB::table('roles')->insert([
                'name'        => 'Admin',
                'guard_name'  => 'web',
                'company_id'  => $company->id,
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ]);

            return ['user' => $user, 'company' => $company];
        });
    }
}
