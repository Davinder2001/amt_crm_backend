<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CompanyIdService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use App\Models\CompanyUser;
use Illuminate\Support\Facades\Validator;
use App\Models\Company;

class AddNewCompanyController extends Controller
{
    /**
     * Store a newly created company.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_name'          => 'required|string|max:255',
            'company_id'            => 'nullable|string|max:50|unique:companies,company_id',
            'company_logo'          => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            // 'package_id'            => 'required|exists:packages,id',
            'business_address'      => 'nullable|string',
            'pin_code'              => 'nullable|string|max:10',
            'business_proof_type'   => 'nullable|string|max:255',
            'business_id'           => 'nullable|string|max:255',
            'business_proof_front'  => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'business_proof_back'   => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $slug = Str::slug($data['company_name']);

        if (Company::where('company_slug', $slug)->exists()) {
            throw ValidationException::withMessages([
                'company_name' => ['Company slug already exists. Please choose a different company name.']
            ]);
        }

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

        $logoPath = null;
        if (!empty($data['company_logo'])) {
            $file     = $data['company_logo'];
            $name     = uniqid('company_logo_') . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/business_proofs'), $name);
            $logoPath = 'uploads/business_proofs/' . $name;
        }

        $companyId = CompanyIdService::generateNewCompanyId();
        $company = Company::create([
            'company_id'            => $companyId,
            'company_name'          => $data['company_name'],
            'company_logo'          => $logoPath,
            'package_id'            => $data['packageId'] ?? 1,
            'company_slug'          => $slug,
            'payment_status'        => 'pending',
            'verification_status'   => 'pending',
            'business_address'      => $data['business_address'] ?? null,
            'pin_code'              => $data['pin_code'] ?? null,
            'business_proof_type'   => $data['business_proof_type'] ?? null,
            'business_id'           => $data['business_id'] ?? null,
            'business_proof_front'  => $frontPath ?? null,
            'business_proof_back'   => $backPath ?? null,
        ]);



        $user = Auth::user();

        $company = CompanyUser::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'user_type' => 'admin'
        ]);


        $role = Role::firstOrCreate([
            'name'       => 'admin',
            'guard_name' => 'web',
            'company_id' => $company->id,
        ]);

        $user->assignRole($role);

        return response()->json([
            'success' => true,
            'message' => 'Company created successfully.',
            'company' => $company,
        ], 201);
    }
}
