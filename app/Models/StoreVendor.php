<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;


class StoreVendor extends Model
{
    use HasFactory;

    protected $table = 'store_vendors';

    protected $fillable = [
        'company_id',
        'vendor_name',
        'vendor_number',
        'vendor_email',
        'vendor_address',
    ];


    /**
     * Get the company that owns the vendor.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }


    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope);
    }
}
