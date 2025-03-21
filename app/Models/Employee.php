<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'employees';

    protected $fillable = [
        'name',
        'email',
        'password',
        'number',
        'user_type',
        'uid',
        'company_id',  
    ];

    // If you're storing passwords, ensure they remain hidden when transforming to arrays/json
    protected $hidden = [
        'password',
    ];

    // If you need password auto-hashing in Laravel 10+
    protected $casts = [
        'password' => 'hashed',
    ];

    /**
     * Relationship: many-to-many with Company.
     * Only include if an employee can belong to multiple companies.
     */
    public function companies()
    {
        return $this->belongsToMany(
            Company::class,
            'company_employee', // Pivot table name
            'employee_id',      // Foreign key on pivot table
            'company_id'        // Related key on pivot table
        )->withTimestamps();
    }

    /**
     * Alternative: If an Employee can only belong to ONE company:
     */
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
}
