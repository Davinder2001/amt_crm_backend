<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyAccount extends Model
{
    protected $fillable = [
        'company_id',
        'bank_name',
        'account_number',
        'ifsc_code',
        'type',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
