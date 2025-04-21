<?php
// app/Models/Item.php

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
        'name',
        'quantity_count',
        'measurement',
        'purchase_date',
        'date_of_manufacture',
        'date_of_expiry',
        'brand_name',
        'replacement',
        'vendor_name',
        'availability_stock',
        'images',
        'cost_price',
        'selling_price',
    ];

    protected $casts = [
        'images'              => 'array',
        'purchase_date'       => 'date',
        'date_of_manufacture' => 'date',
        'date_of_expiry'      => 'date',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
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

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function variants()
    {
        return $this->hasMany(ItemVariant::class);
    }

    /**
     * Corrected pivot: table = item_tax,
     *   store_item_id → this model’s FK,
     *   tax_id        → related model’s FK
     */
    public function taxes()
    {
        return $this->belongsToMany(
            Tax::class,
            'item_tax',
            'store_item_id',
            'tax_id'
        )
        ->withTimestamps();
    }
}
