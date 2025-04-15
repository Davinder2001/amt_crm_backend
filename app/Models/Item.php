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
        'images' => 'array',
        'purchase_date' => 'date',
        'date_of_manufacture' => 'date',
        'date_of_expiry' => 'date',
    ];

    /**
     * Many-to-Many: Categories related to this item.
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_item');
    }

    /**
     * Belongs to a company.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * One-to-Many: Item Variants
     */
    public function variants()
    {
        return $this->hasMany(ItemVariant::class);
    }

    /**
     * Automatically apply company scope to all queries.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }
}
