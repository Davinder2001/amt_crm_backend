<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Services\UserUidService;
use Cmgmyr\Messenger\Traits\Messagable;
use App\Models\UserMeta;
use App\Models\Scopes\CompanyScope;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, Messagable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'number',
        'user_type',
        'uid',
        'user_status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];



    /**
     * Auto-generate UID when creating a new user
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->uid)) {
                $user->uid = UserUidService::generateNewUid();
            }
        });
    }

    /**
     * Add global scopes (like CompanyScope)
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    /**
     * Define the relationship with the Company model
     */
    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_user')->withPivot('user_type')->withTimestamps();
    }

    /**
     * Define the relationship with the UserMeta model
     */
    public function meta()
    {
        return $this->hasMany(UserMeta::class);
    }

    /**
     * Define the relationship with the attendances model
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Define the relationship with the EmployeeDetail model
     */
    public function employeeDetail()
    {
        return $this->hasOne(EmployeeDetail::class, 'user_id', 'id');
    }

    /**
     * Define the relationship with the SalaryHistory model
     */
    public function salaryHistories()
    {
        return $this->hasMany(SalaryHistory::class, 'user_id', 'id')->latest();
    }

    /**
     * Define the relationship with the Salary model
     */
    public function salaryDetails()
    {
        return [
            'basic' => 50000,
            'hra' => 10000,
            'allowances' => 5000,
            'total' => 50000 + 10000 + 5000,
        ];
    }
}
