<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Services\UserUidService;
use \App\Models\UserMeta;
use App\Models\Scopes\CompanyScope;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'number',
        'user_type',
        'uid',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->uid)) {
                $user->uid = UserUidService::generateNewUid();
            }
        });
    }


    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_user')->withPivot('user_type')->withTimestamps();
    }
    

    public function meta()
    {
        return $this->hasMany(UserMeta::class);
    }
    
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }


    public function salaryDetails()
    {
        
        return [
            'basic' => 50000,
            'hra' => 10000,
            'allowances' => 5000,
            'total' => 50000 + 10000 + 5000,
        ];
    }

    public function employeeDetail()
    {
        return $this->hasOne(EmployeeDetail::class, 'user_id', 'id');
    }
    
    public function salaryHistories()
    {
        return $this->hasMany(SalaryHistory::class, 'user_id', 'id')->latest();
    }

}

