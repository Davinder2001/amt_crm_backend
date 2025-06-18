<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;


class MeasuringUnit extends Model
{
    protected $fillable = [
        'name',
        'company_id',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }
}
