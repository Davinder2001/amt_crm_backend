<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;

class Item extends Model
{
    use HasFactory;

    protected $table = 'store_items';

    /*------------------------------------------------------------------
    | Mass-assignable columns
    *------------------------------------------------------------------*/
    protected $fillable = [
        'item_code',
        'company_id',
        'brand_id',
        'name',
        'measurement',
        'featured_image',
        'images',
        'availability_stock',
        'catalog',
        'online_visibility',
    ];

    /*------------------------------------------------------------------
    | Attribute casting
    *------------------------------------------------------------------*/
    protected $casts = [
        'images'            => 'array',
        'catalog'           => 'boolean',
        'online_visibility' => 'boolean',
    ];

    /*------------------------------------------------------------------
    | Global scope
    *------------------------------------------------------------------*/
    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    /*------------------------------------------------------------------
    | Relationships
    *------------------------------------------------------------------*/

    public function batches()
    {
        return $this->hasMany(ItemBatch::class);
    }

    public function categories()
    {
        return $this->belongsToMany(
            Category::class,
            'category_item',
            'store_item_id',
            'category_id'
        );
    }

    public function measurementDetails()
    {
        return $this->belongsTo(MeasuringUnit::class, 'measurement');
    }

    public function brand()
    {
        return $this->belongsTo(StoreItemBrand::class, 'brand_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function variants()
    {
        return $this->hasMany(ItemVariant::class);
    }

    public function taxes()
    {
        return $this->belongsToMany(
            Tax::class,
            'item_tax',
            'store_item_id',
            'tax_id'
        )->withTimestamps();
    }

    public function vendor()
    {
        return $this->belongsTo(StoreVendor::class, 'vendor_id');
    }
}
