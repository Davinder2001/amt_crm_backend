<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreVendor extends Model
{
    use HasFactory;

    // Specify the table name.
    protected $table = 'store_vendors';

    // Only vendor_name and company_id are mass assignable.
    protected $fillable = [
        'company_id',
        'vendor_name',
    ];

    /**
     * Get the company that owns the vendor.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
