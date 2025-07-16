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
        $baseImageUrl = rtrim(config('app.url'), '/');
        $company = $this->companies->first(); 

        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'email'          => $this->email,
            'number'         => $this->number,
            'profilePicture' => $this->getMainProfilePictureUrl($baseImageUrl),
            'user_type'      => $this->user_type,
            'user_status'    => $this->user_status,
            'company_name'   => $company?->company_name,
            'company_id'     => $company?->id,
            'company_slug'   => $company?->company_slug,
            'roles'          => RoleResource::collection($this->whenLoaded('roles')),

            'employee_details' => $this->whenLoaded('employeeDetail', function () use ($baseImageUrl) {
                $detail = $this->employeeDetail;
                $shift = $detail->shift;

                return [
                    'user_id'                   => $detail->user_id ?? null,
                    'salary'                    => $detail->salary ?? null,
                    'dateOfHire'                => $detail->dateOfHire ?? null,
                    'joiningDate'               => $detail->joiningDate ?? null,
                    'shift'                     => $shift ? [
                        'id'         => $shift->id,
                        'name'       => $shift->shift_name,
                        'start_time' => $shift->start_time,
                        'end_time'   => $shift->end_time,
                    ] : null,
                    'address'                   => $detail->address ?? null,
                    'nationality'               => $detail->nationality ?? null,
                    'dob'                       => $detail->dob ?? null,
                    'religion'                  => $detail->religion ?? null,
                    'maritalStatus'             => $detail->maritalStatus ?? null,
                    'id_proof_type'             => $detail->id_proof_type ?? null,
                    'id_proof_value'            => $detail->id_proof_value ?? null,
                    'emergencyContact'          => $detail->emergencyContact ?? null,
                    'emergencyContactRelation'  => $detail->emergencyContactRelation ?? null,
                    'currentSalary'             => $detail->currentSalary ?? null,
                    'workLocation'              => $detail->workLocation ?? null,
                    'joiningType'               => $detail->joiningType ?? null,
                    'department'                => $detail->department ?? null,
                    'previousEmployer'          => $detail->previousEmployer ?? null,
                    'medicalInfo'               => $detail->medicalInfo ?? null,
                    'bankName'                  => $detail->bankName ?? null,
                    'accountNo'                 => $detail->accountNo ?? null,
                    'ifscCode'                  => $detail->ifscCode ?? null,
                    'panNo'                     => $detail->panNo ?? null,
                    'upiId'                     => $detail->upiId ?? null,
                    'addressProof'              => $detail->addressProof ?? null,
                    'profilePicture'            => $detail->profilePicture
                        ? $baseImageUrl . '/' . ltrim($detail->profilePicture, '/')
                        : null,
                ];
            }),
        ];
    }

    /**
     * Get the main profile picture URL from either user or employee detail.
     */
    protected function getMainProfilePictureUrl(string $baseUrl): ?string
    {
        $detailPic = $this->employeeDetail?->profilePicture;
        if ($detailPic) {
            return $baseUrl . '/' . ltrim($detailPic, '/');
        }

        return null;
    }
}
