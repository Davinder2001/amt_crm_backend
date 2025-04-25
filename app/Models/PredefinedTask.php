<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;

class PredefinedTask extends Model
{
    protected $fillable = [
        'name', 
        'description', 
        'assigned_by', 
        'assigned_to', 
        'company_id',
        'assigned_role', 
        'recurrence_type', 
        'recurrence_days',
        'recurrence_start_date', 
        'recurrence_end_date', 
        'notify'
    ];

    protected $casts = [
        'recurrence_days' => 'array',
        'recurrence_start_date' => 'date',
        'recurrence_end_date' => 'date',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }
}
