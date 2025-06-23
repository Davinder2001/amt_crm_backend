<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemBatch extends Model
{
    protected $fillable = [
        'company_id',
        'item_id',
        'quantity',
        'purchase_date',
        'date_of_manufacture',
        'date_of_expiry',
        'replacement',
        'cost_price',
        'regular_price',
        'sale_price',
        'product_type',
        'unit_of_measure',
        'unit_id',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function variants()
    {
        return $this->hasMany(ItemVariant::class, 'batch_id');
    }


    public function unit()
    {
        return $this->belongsTo(MeasuringUnit::class, 'unit_id');
    }
}
