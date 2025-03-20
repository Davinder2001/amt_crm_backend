<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'employees';

    protected $fillable = [
        'name',
        'email',
        'password',
        'number',
        'user_type',
        'uid',
        'company_id',
        'company_name',
        'company_slug',
    ];


    /**
     * The companies that belong to the employee.
     */
    public function companies()
    {
           return $this->belongsToMany(Company::class, 'company_employee', 'employee_id', 'company_id');
    }
}
