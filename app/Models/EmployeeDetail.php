<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;

class EmployeeDetail extends Model
{
    protected $table = 'employee_details';

    protected $fillable = [
        'user_id',
        'salary',
        'dateOfHire',
        'joiningDate',
        'shiftTimings',
        'address',
        'nationality',
        'dob',
        'religion',
        'maritalStatus',
        'passportNo',
        'emergencyContact',
        'emergencyContactRelation',
        'currentSalary',
        'workLocation',
        'joiningType',
        'department',
        'previousEmployer',
        'medicalInfo',
        'bankName',
        'accountNo',
        'ifscCode',
        'panNo',
        'upiId',
        'addressProof',
        'profilePicture',
    ];


    /**
     * Relationship: EmployeeDetail belongs to an Employee.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Relationship: EmployeeDetail belongs to shift.
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    /**
     * Relationship: EmployeeDetail belongs to a Company.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }
}
