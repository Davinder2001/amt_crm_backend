<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Scopes\CompanyScope;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'parent_id',
    ];


    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope());
    }

    /**
     * Items related to this category
     */
    public function items()
    {
        return $this->belongsToMany( Item::class, 'category_item', 'category_id', 'store_item_id' );
    }
    

    /**
     * Parent category relationship
     */
    // public function parent()
    // {
    //     return $this->belongsTo(Category::class, 'parent_id');
    // }

    /**
     * Child categories relationship
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive', 'items');
    }

    public function item()
    {
        return $this->belongsToMany( Item::class, 'category_item', 'category_id', 'store_item_id');
    }

}
