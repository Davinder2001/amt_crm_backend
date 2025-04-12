<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
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

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    // app/Models/Attendance.php

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }


}
