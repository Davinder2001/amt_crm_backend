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
        'category',
        'vendor_name',
        'availability_stock',
        'images',
        'cost_price',
        'selling_price',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

}
