<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ];

    protected $casts = [
        'images' => 'array', 
    ];


    /**
     * Get the company that owns the item.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
