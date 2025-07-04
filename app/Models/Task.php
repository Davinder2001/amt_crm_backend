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
        'description',
        'assigned_by',
        'assigned_to',
        'company_id',
        'assigned_role',
        'start_date',
        'end_date',
        'attachments',
        'notify',
        'status',
    ];

    protected $casts = [
        'start_date'  => 'datetime',
        'end_date'    => 'datetime',
        'notify'      => 'boolean',
        'attachments' => 'array',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope());
    }

    // Relations
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
        return $this->belongsTo(Company::class);
    }

    public function getAttachmentUrlAttribute()
    {
        return $this->attachment_path 
            ? asset($this->attachment_path) 
            : null;
    }
}