<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;

class Role extends Model
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

    /**
     * Define the relationship with the Permission model.
     */
    public function permissions()
    {
        return $this->belongsToMany(
            \Spatie\Permission\Models\Permission::class,
            'role_has_permissions',
            'role_id',
            'permission_id'
        );
    }
}
