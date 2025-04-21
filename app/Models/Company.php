<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'company_id',
        'admin_id',
        'company_slug',
        'payment_status',
        'verification_status',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * The attributes that should be cast to native types.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * The attributes that should be cast to native types.
     */
    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

}

