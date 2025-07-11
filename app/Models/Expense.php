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
        'file_path',
    ];

    protected $appends = ['file_url'];

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

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
            ? asset($this->file_path)
            : null;
    }

    /**
     * ✅ Expense ↔ Items (Many-to-Many) with batch_id in pivot
     */
    public function items()
    {
        return $this->belongsToMany(Item::class, 'expense_item')
                    ->withPivot('batch_id') // ✅ include batch_id
                    ->withTimestamps();
    }

    /**
     * ✅ Expense ↔ Invoices (Many-to-Many)
     */
    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'expense_invoice')->withTimestamps();
    }

    /**
     * ✅ Expense ↔ Users (Many-to-Many)
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'expense_user')->withTimestamps();
    }
}
