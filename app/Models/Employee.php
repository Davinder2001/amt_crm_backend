<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Employee extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'employee_id',
        'name',
        'email',
        'mobile_no',
        'password',
        'aadhar_card_no',
        'admin_id',
        'role_id',
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

        static::creating(function ($employee) {
            $employee->employee_id = 'EMP' . str_pad(Employee::max('id') + 1, 6, '0', STR_PAD_LEFT);
        });
    }


    /**
     * Define the relationship with Admin.
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }


}
