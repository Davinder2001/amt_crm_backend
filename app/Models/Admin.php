<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
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
            $admin->uid = 'AMT' . str_pad(Admin::max('id') + 1, 6, '0', STR_PAD_LEFT);
        });
    }

    // public function employees()
    // {
    //     return $this->hasMany(Employee::class);
    // }

    // public function rolesAssigned()
    // {
    //     return $this->hasMany(EmpRole::class, 'assigned_by_admin_id');
    // }
}
