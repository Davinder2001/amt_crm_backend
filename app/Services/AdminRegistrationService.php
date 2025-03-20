<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

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
                'error' => ['User already exists. Please login and upgrade the package.']
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
                'uid'      => $uid,
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
                'number'   => $data['number'],
                'user_type' => 'admin',
            ]);

            $user->companies()->attach($company->id, ['role' => 'admin']);


            DB::table('roles')->insert([
                'name'        => 'admin',
                'guard_name'  => 'web',
                'company_id'  => $company->id, 
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ]);
            
            $user->assignRole('admin');

            return ['user' => $user, 'company' => $company];
        });
    }
}
