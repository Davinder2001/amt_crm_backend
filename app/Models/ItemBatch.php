<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemBatch extends Model
{
    protected $fillable = [
        'company_id',
        'vendor_id',
        'item_id',
        'invoice_number',
        'quantity',
        'purchase_date',
        'date_of_manufacture',
        'date_of_expiry',
        'replacement',
        'cost_price',
        'tax_type',
        'regular_price',
        'sale_price',
        'product_type',
        'unit_of_measure',
        'units_in_peace',
        'price_per_unit',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function variants()
    {
        return $this->hasMany(ItemVariant::class, 'batch_id');
    }

    public function vendor()
    {
        return $this->belongsTo(StoreVendor::class, 'vendor_id');
    }

    public function unit()
    {
        return $this->belongsTo(MeasuringUnit::class, 'unit_id');
    }
}
