<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Shift;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'company_id',
        'company_logo',
        'admin_id',
        'company_slug',
        'package_id',
        'order_id',
        'transation_id',
        'payment_recoad_status',
        'payment_status',
        'verification_status',
        'business_address',
        'pin_code',
        'business_proof_type',
        'business_id',
        'business_proof_front',
        'business_proof_back',
        'subscription_date',
        'subscription_status',
    ];

    /**
     * The user who originally registered / administers this company.
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * All users associated with this company.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Attendance records for this company.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Shift definitions for this company.
     */
    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }
}
