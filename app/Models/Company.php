<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'company_name',
        'company_slug',
        'payment_status',
        'verification_status',
    ];

    /**
     * Automatically generate a sequential company ID.
     */
    public static function generateCompanyId(): string
    {
        $lastCompany = self::latest('id')->first();

        $lastId = $lastCompany ? $lastCompany->company_id : null;

        $nextNumber = $lastId
            ? ((int) str_replace('AMT', '', $lastId) + 1)
            : 1;

        return 'AMT' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
