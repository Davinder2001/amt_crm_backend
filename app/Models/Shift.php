<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'shift_name',
        'start_time',
        'end_time',
    ];


    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
