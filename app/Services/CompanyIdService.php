<?php

namespace App\Services;

use App\Models\Company;

class CompanyIdService
{
    /**
     * Generate a new, sequential Company ID in the format: AMTCOM0000001, AMTCOM0000002, etc.
     *
     * @return string
     */
    public static function generateNewCompanyId(): string
    {
        $lastCompanyId = Company::max('company_id');

        if ($lastCompanyId) {
            $numericPart = (int) preg_replace('/\D/', '', $lastCompanyId);
            $newNumber   = $numericPart + 1;
        } else {
            $newNumber = 1;
        }

        return 'AMTCOM' . str_pad($newNumber, 7, '0', STR_PAD_LEFT);
    }
}
