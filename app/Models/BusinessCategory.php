<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',           // Name of the business category
        'description',    // Description of the category (optional)
    ];

    public function packages()
    {
        return $this->belongsToMany(Package::class, 'business_category_package');
    }
}
