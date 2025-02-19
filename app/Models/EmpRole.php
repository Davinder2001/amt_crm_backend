<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_name',
        'admin_id',
    ];

    // public function assignedEmployee()
    // {
    //     return $this->belongsTo(Employee::class, 'assigned_to_employee_id');
    // }

    // public function assignedByAdmin()
    // {
    //     return $this->belongsTo(Admin::class, 'assigned_by_admin_id');
    // }
}
