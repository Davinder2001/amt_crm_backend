<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;
use App\Models\Company; // Make sure you have this model

class Task extends Model
{
    protected $fillable = ['name', 'assigned_by', 'assigned_to', 'deadline', 'company_id'];

    /**
     * Boot the model and apply the global CompanyScope.
     */
    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope());
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
