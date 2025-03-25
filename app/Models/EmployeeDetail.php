<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDetail extends Model
{
    protected $table = 'employee_details';

    protected $fillable = [
        'user_id',
        'salary',
        'dateOfHire',
        'joiningDate',
        'shiftTimings',
    ];

    /**
     * Relationship: EmployeeDetail belongs to an Employee.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
