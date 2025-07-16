<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;

class Customer extends Model
{
    use HasFactory;
    protected $fillable = [
        'company_id',
        'name',
        'number',
        'email',
        'address',
        'pincode',
    ];


    /**
     * The attributes that should be cast to native types.
     */
    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope());
    }

    /**
     * The attributes that should be cast to native types.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
