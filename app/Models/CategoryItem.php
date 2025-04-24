<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CategoryItem extends Model
{
    use HasFactory;

    protected $table = 'category_item';
    public $timestamps = false;

    protected $fillable = [
        'store_item_id',
        'category_id',
    ];


    /**
     * The attributes that should be cast to native types.
     */
    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope());
    }

    /**
     * The attributes that should be cast to native types.
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'store_item_id');
    }

    /**
     * The attributes that should be cast to native types.
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
