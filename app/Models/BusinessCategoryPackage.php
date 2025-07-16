<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessCategoryPackage extends Model
{
    protected $table = 'business_category_package';

    protected $fillable = [
        'business_category_id',
        'package_id',
    ];

    public function packages()
    {
        return $this->belongsToMany(Package::class, 'business_category_package', 'business_category_id', 'package_id');
    }
}
