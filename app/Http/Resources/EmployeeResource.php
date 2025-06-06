<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $baseImageUrl = config('app.image_uri');
        $company = $this->companies->first(); 



        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'email'         => $this->email,
            'number'        => $this->number,
            'profilePicture' => $baseImageUrl. '/' .$this->profile_image,
            'user_type'     => $this->user_type,
            'user_status'   => $this->user_status,
            'company_name'  => $company ? $company->company_name : null,
            'company_id'    => $company ? $company->id : null,
            'company_slug'  => $company ? $company->company_slug : null,
            'roles'         => RoleResource::collection($this->whenLoaded('roles')),

           'employee_details' => $this->whenLoaded('employeeDetail', function () {
              
            $shift = $this->employeeDetail->shift;
            
            return [
                'user_id'                   => $this->employeeDetail->user_id ?? null,
                'salary'                   => $this->employeeDetail->salary ?? null,
                'dateOfHire'               => $this->employeeDetail->dateOfHire ?? null,
                'joiningDate'              => $this->employeeDetail->joiningDate ?? null,
                   'shift' => $shift ? [
                        'id'          => $shift->id,
                        'name'        => $shift->shift_name,
                        'start_time'  => $shift->start_time,
                        'end_time'    => $shift->end_time,
                ] : null,
                'address'                  => $this->employeeDetail->address ?? null,
                'nationality'              => $this->employeeDetail->nationality ?? null,
                'dob'                      => $this->employeeDetail->dob ?? null,
                'religion'                 => $this->employeeDetail->religion ?? null,
                'maritalStatus'            => $this->employeeDetail->maritalStatus ?? null,
                'id_proof_type'            => $this->employeeDetail->id_proof_type ?? null,
                'id_proof_value'           => $this->employeeDetail->id_proof_value ?? null,
                'emergencyContact'         => $this->employeeDetail->emergencyContact ?? null,
                'emergencyContactRelation' => $this->employeeDetail->emergencyContactRelation ?? null,
                'currentSalary'            => $this->employeeDetail->currentSalary ?? null,
                'workLocation'             => $this->employeeDetail->workLocation ?? null,
                'joiningType'              => $this->employeeDetail->joiningType ?? null,
                'department'               => $this->employeeDetail->department ?? null,
                'previousEmployer'         => $this->employeeDetail->previousEmployer ?? null,
                'medicalInfo'              => $this->employeeDetail->medicalInfo ?? null,
                'bankName'                 => $this->employeeDetail->bankName ?? null,
                'accountNo'                => $this->employeeDetail->accountNo ?? null,
                'ifscCode'                 => $this->employeeDetail->ifscCode ?? null,
                'panNo'                    => $this->employeeDetail->panNo ?? null,
                'upiId'                    => $this->employeeDetail->upiId ?? null,
                'addressProof'             => $this->employeeDetail->addressProof ?? null,
                'profilePicture'           => $this->employeeDetail->profilePicture ?? null,
            ];
        }),
        ];
    }
}
