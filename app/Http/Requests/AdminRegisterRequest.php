<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AdminRegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'first_name'                   => 'required|string|max:255',
            'last_name'                    => 'nullable|string|max:255',
            'number'                       => 'required|string|max:10',
            'email'                        => 'required|email|max:255|unique:users,email',
            'password'                     => 'required|string|min:8|confirmed',
            'company_name'                 => 'required|string|max:255',
            'business_address'             => 'nullable|string',
            'pin_code'                     => 'nullable|string|max:20',
            'business_proof_type'          => 'nullable|string|max:255',
            'business_id'                  => 'nullable|string|max:255',
            'business_proof_image_front'   => 'nullable|mimes:jpg,jpeg,png,pdf|max:5120',
            'company_logo'                 => 'nullable|mimes:jpg,jpeg,png,pdf|max:5120',
            'business_proof_image_back'    => 'nullable|mimes:jpg,jpeg,png,pdf|max:5120',
            'packageId'                    => 'nullable|exists:packages,id',

        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
