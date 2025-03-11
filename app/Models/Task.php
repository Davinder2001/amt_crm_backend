<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;
use App\Models\Company;
use App\Models\User;

class Task extends Model
{
    protected $fillable = [
        'name', 
        'assigned_by', 
        'assigned_to', 
        'deadline', 
        'company_id', 
        'status',       
    ];


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



    public function getTaskImagesAttribute($value)
    {
        return json_decode($value, true);
    }
}
