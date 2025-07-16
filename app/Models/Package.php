<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = [
        'name',
        'monthly_price',
        'annual_price',
        'three_years_price',
    ];

    public function businessCategories()
    {
        return $this->belongsToMany(BusinessCategory::class, 'business_category_package');
    }

    // app/Models/Package.php

    public function limits()
    {
        return $this->hasMany(PackageLimit::class);
    }
}
