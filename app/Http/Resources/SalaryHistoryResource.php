<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaryHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'previous_salary' => $this->previous_salary,
            'new_salary'      => $this->new_salary,
            'increment_date'  => $this->increment_date,
            'reason'          => $this->reason,
        ];
    }
    
}
