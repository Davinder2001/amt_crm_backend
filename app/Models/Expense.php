<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'heading',
        'description',
        'price',
        'status',
        'tags',
        'file_path',
    ];

    protected $appends = ['file_url'];

    /**
     * Get the company that owns the expense.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Accessor to get the public file URL.
     */
    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path
            ? asset("storage/expenses/{$this->file_path}")
            : null;
    }
    
    /**
     * Relationship: EmployeeDetail belongs to a Company.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }
}
