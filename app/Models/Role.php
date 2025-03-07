<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use App\Models\Scopes\CompanyScope;

class Role extends SpatieRole
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'guard_name',
        'company_id',
    ];

    /**
     * Boot the model and apply the CompanyScope globally.
     */
    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope);
    }
}
