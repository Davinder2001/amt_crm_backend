<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;

class Item extends Model
{
    use HasFactory;

    protected $table = 'store_items';

    protected $fillable = [
        'item_code',
        'company_id',
        'brand_id',
        'name',
        'quantity_count',
        'invoice_no',
        'measurement',
        'purchase_date',
        'product_type',
        'featured_image',
        'date_of_manufacture',
        'date_of_expiry',
        'brand_name',
        'replacement',
        'vendor_name',
        'vendor_id',
        'images',
        'cost_price',
        'regular_price',
        'sale_price',
    ];

    protected $casts = [
        'images'              => 'array',
        'purchase_date'       => 'date',
        'date_of_manufacture' => 'date',
        'date_of_expiry'      => 'date',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    /**
     * The batches associated with the item.
     */
    public function batches()
    {
        return $this->hasMany(ItemBatch::class);
    }


    /**
     * The attributes that should be cast to native types.
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_item', 'store_item_id', 'category_id');
    }

    /**
     * The attributes that should be cast to native types.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * The attributes that should be cast to native types.
     */
    public function variants()
    {
        return $this->hasMany(ItemVariant::class);
    }

    /**
     * The attributes that should be cast to native types.
     */
    public function taxes()
    {
        return $this->belongsToMany(Tax::class, 'item_tax', 'store_item_id', 'tax_id')->withTimestamps();
    }


    public function vendor()
    {
        return $this->belongsTo(StoreVendor::class, 'vendor_id');
    }
}
