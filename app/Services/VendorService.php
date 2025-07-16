<?php
namespace App\Services;

use App\Models\StoreVendor;

class VendorService
{
    public static function createIfNotExists(string $vendorName, int $companyId): void
    {
        StoreVendor::firstOrCreate([
            'vendor_name' => $vendorName,
            'company_id'  => $companyId,
        ]);
    }
}
