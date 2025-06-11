<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemBatch extends Model
{
    protected $fillable = [
        'company_id',
        'item_id',
        'batch_number',
        'purchase_price',
        'quantity',
    ];
}
