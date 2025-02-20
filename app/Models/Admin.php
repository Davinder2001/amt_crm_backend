<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable implements JWTSubject
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'mobile_no',
        'password',
        'aadhar_card_no',
        'uid',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($admin) {
            $latestAdminId = Admin::max('id') ?? 0;
            $admin->uid = 'AMT' . str_pad($latestAdminId + 1, 6, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Relationship: One Admin has many Employees
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Relationship: One Admin assigns many Roles
     */
    public function rolesAssigned()
    {
        return $this->hasMany(EmpRole::class, 'assigned_by_admin_id');
    }

    /**
     * Get the identifier that will be stored in JWT
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key-value array, containing any custom claims to be added to JWT
     */
    public function getJWTCustomClaims()
    {
        return ['role' => 'admin'];
    }
}
