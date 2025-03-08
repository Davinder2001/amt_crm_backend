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
            $company = Company::create([
                'company_id'          => Company::generateCompanyId(),
                'company_name'        => $data['company_name'],
                'company_slug'        => $slug,
                'payment_status'      => 'pending',
                'verification_status' => 'pending',
            ]);

            $user = User::create([
                'name'       => $data['name'],
                'email'      => $data['email'],
                'password'   => Hash::make($data['password']),
                'role'       => 'admin',
                'company_id' => $company->id,
            ]);

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
