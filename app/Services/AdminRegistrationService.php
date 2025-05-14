<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use App\Services\CompanyIdService;
use App\Services\UserUidService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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

        if (User::where('number', $data['number'])->exists()) {
            throw ValidationException::withMessages([
                'number' => ['User already exists. Please login and upgrade the package.']
            ]);
        }

        return DB::transaction(function () use ($data, $slug) {

            $companyId = CompanyIdService::generateNewCompanyId();
            $uid       = UserUidService::generateNewUid();

            $frontPath = null;
            if (!empty($data['business_proof_image_front'])) {
                $file     = $data['business_proof_image_front'];
                $name     = uniqid('proof_front_') . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/business_proofs'), $name);
                $frontPath = 'uploads/business_proofs/' . $name;
            }

            $backPath = null;
            if (!empty($data['business_proof_image_back'])) {
                $file     = $data['business_proof_image_back'];
                $name     = uniqid('proof_back_') . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/business_proofs'), $name);
                $backPath = 'uploads/business_proofs/' . $name;
            }

            $company = Company::create([
                'company_id'            => $companyId,
                'company_name'          => $data['company_name'],
                'package_id'            => $data['package_id'],
                'company_slug'          => $slug,
                'payment_status'        => 'pending',
                'verification_status'   => 'pending',
                'business_address'      => $data['business_address'],
                'pin_code'              => $data['pin_code'],
                'business_proof_type'   => $data['business_proof_type'],
                'business_id'           => $data['business_id'],
                'business_proof_front'  => $frontPath,
                'business_proof_back'   => $backPath,
            ]);

            $user = User::create([
                'uid'         => $uid,
                'name'        => trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')),
                'email'       => $data['email'],
                'password'    => Hash::make($data['password']),
                'number'      => $data['number'],
                'user_type'   => 'admin',
            ]);

            $user->companies()->attach($company->id, ['user_type' => 'admin']);

            $role = Role::firstOrCreate([
                'name'       => 'admin',
                'guard_name' => 'web',
                'company_id' => $company->id,
            ]);
            $user->assignRole($role);

            return ['user' => $user, 'company' => $company];
        });
    }
}
