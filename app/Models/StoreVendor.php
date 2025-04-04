<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreVendor extends Model
{
    use HasFactory;

    protected $table = 'store_vendors';

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
