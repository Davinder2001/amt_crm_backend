<?php
// app/Models/ItemTax.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemTax extends Model
{
    use HasFactory;

    protected $table = 'item_tax';

    protected $fillable = [
        'store_item_id',
        'tax_id',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'store_item_id');
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }
}
