<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageLimit extends Model
{
    protected $fillable = [
        'package_id',
        'variant_type',
        'employee_numbers',
        'items_number',
        'daily_tasks_number',
        'invoices_number',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
