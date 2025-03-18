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



        $getNumber = $data['number']; 
        $alreadyUser = User::where('number', $getNumber)->first();
        
        if ($alreadyUser) {
            throw ValidationException::withMessages([
                'error' => ['Already a user login and upgrade the package']
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

            $uid = User::generateUid();

            $user = User::create([
                'uid'        => $uid,
                'name'       => $data['name'],
                'email'      => $data['email'],
                'password'   => Hash::make($data['password']),
                'role'       => 'admin',
                'number'     => $data['number'],
                'company_id' => $company->id,
                'user_type' => 'admin',
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
