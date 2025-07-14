<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Package extends Model
{
    protected $fillable = [
        'name',
        'package_type',
        'user_id',
        'annual_price',
        'three_years_price',
        'employee_limit',
        'chat',
        'task',
        'hr',
    ];

    protected $casts = [
        'chat' => 'boolean',
        'task' => 'boolean',
        'hr'   => 'boolean',
    ];

    /**
     * Many-to-Many: Package ↔ BusinessCategory
     */
    public function businessCategories(): BelongsToMany
    {
        return $this->belongsToMany(BusinessCategory::class, 'business_category_package');
    }

    /**
     * Belongs-To: Package ↔ User (specific type)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
