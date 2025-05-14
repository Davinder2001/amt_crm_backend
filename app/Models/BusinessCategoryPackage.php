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
}
