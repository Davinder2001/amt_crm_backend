<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;


class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'shift_name',
        'start_time',
        'end_time',
        'weekly_off_day',
    ];


    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope);
    }
}
