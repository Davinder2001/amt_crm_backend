<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use App\Services\CompanyIdService;
use App\Services\UserUidService;

class AdminRegistrationService
{
    public function register(array $data, $statusResponse, $orderId)
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

        return DB::transaction(function () use ($data, $slug, $statusResponse, $orderId) {
            $companyId = CompanyIdService::generateNewCompanyId();
            $uid       = UserUidService::generateNewUid();
            $frontPath = $this->uploadFile($data['business_proof_image_front'] ?? null, 'proof_front');
            $backPath  = $this->uploadFile($data['business_proof_image_back'] ?? null, 'proof_back');
            $logoPath  = $this->uploadFile($data['company_logo'] ?? null, 'company_logo');

            $company = Company::create([
                'company_id'            => $companyId,
                'company_name'          => $data['company_name'],
                'company_logo'          => $logoPath,
                'package_id'            => $data['packageId'],
                'business_category'     => $data['business_category_id'],
                'company_slug'          => $slug,
                'payment_status'        => 'completed',
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

            $status            = strtoupper($statusResponse->json('state'));
            $paymentCheck      = $statusResponse->json();
            $paymentMode       = $paymentCheck['paymentDetails'][0]['paymentMode'] ?? null;
            $transactionId     = $paymentCheck['order_id'] ;
            $transactionAmount = ($paymentCheck['amount'] ?? 0) / 100;

            if ($orderId && !Payment::where('order_id', $orderId)->exists()) {
                $nowIST = Carbon::now('Asia/Kolkata');

                Payment::create([
                    'user_id'             => $user->id,
                    'order_id'            => $orderId,
                    'transaction_id'      => $transactionId,
                    'payment_status'      => $status,
                    'payment_method'      => $paymentMode,
                    'payment_reason'      => 'Company registration for package ID ' . $data['packageId'],
                    'payment_fail_reason' => null,
                    'transaction_amount'  => $transactionAmount,
                    'payment_date'        => $nowIST->format('d/m/Y'),
                    'payment_time'        => $nowIST->format('h:i A'),
                ]);
            }

            // Assign user to company
            $user->companies()->attach($company->id, ['user_type' => 'admin']);

            // Assign role
            $role = Role::firstOrCreate([
                'name'       => 'admin',
                'guard_name' => 'web',
                'company_id' => $company->id,
            ]);
            $user->assignRole($role);

            return ['user' => $user, 'company' => $company];
        });
    }

    private function uploadFile($file, $prefix)
    {
        if ($file) {
            $name = uniqid($prefix . '_') . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/business_proofs'), $name);
            return 'uploads/business_proofs/' . $name;
        }
        return null;
    }
}
