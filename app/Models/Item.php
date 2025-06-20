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
        /* identifiers */
        'item_code',
        'company_id',

        /* brand / vendor */
        'brand_id',
        'brand_name',
        'vendor_id',
        'vendor_name',

        /* core item data */
        'name',
        'quantity_count',
        'measurement',

        /* unit / pricing meta */
        'unit_of_measure',
        'units_in_peace',
        'price_per_unit',

        /* dates */
        'purchase_date',
        'date_of_manufacture',
        'date_of_expiry',

        /* product meta */
        'product_type',
        'sale_type',
        'replacement',
        'featured_image',

        /* prices */
        'cost_price',
        'regular_price',
        'sale_price',

        /* media & visibility */
        'images',
        'availability_stock',
        'catalog',
        'online_visibility',

        /* invoice FK */
        'invoice_id',
    ];

    /*------------------------------------------------------------------
    | Attribute casting
    *------------------------------------------------------------------*/
    protected $casts = [
        'images'              => 'array',
        'catalog'             => 'boolean',
        'online_visibility'   => 'boolean',
        'purchase_date'       => 'date',
        'date_of_manufacture' => 'date',
        'date_of_expiry'      => 'date',
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

    // App\Models\Item.php
    public function measuringUnit()   // call it whatever you like
    {
        return $this->belongsTo(MeasuringUnit::class, 'measurement');
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
