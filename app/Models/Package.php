<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = [
        'name',
        'employee_numbers',
        'items_number',
        'daily_tasks_number',
        'invoices_number',
    ];
}
