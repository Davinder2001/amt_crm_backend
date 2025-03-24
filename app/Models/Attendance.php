<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'company_id',
        'attendance_date',
        'clock_in',
        'clock_out',
        'status',
    ];

    /**
     * Get the user that owns this attendance record.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company associated with this attendance record.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
