<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;


class CompanyAccount extends Model
{
    protected $fillable = [
        'company_id',
        'bank_name',
        'account_number',
        'ifsc_code',
        'type',
    ];


    /**
     * The attributes that should be cast to native types.
     */
    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope());
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
