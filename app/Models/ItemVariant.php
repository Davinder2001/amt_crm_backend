<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemVariant extends Model
{
    protected $fillable = [
        'item_id',
        'variant_regular_price',
        'variant_sale_price',
        'variant_units_in_peace',
        'variant_price_per_unit',
        'quntity',
        'stock',
        'images',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    /*--------------------------------------------------------------
    | Relationships
    *--------------------------------------------------------------*/
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    // App\Models\ItemVariant.php

    public function attributeValues()
    {
        return $this->belongsToMany(AttributeValue::class, 'item_variant_attribute_value')
            ->with('attribute')  // this is critical!
            ->withTimestamps();
    }


    // app/Models/ItemVariant.php

    public function batch()
    {
        return $this->belongsTo(ItemBatch::class, 'batch_id');
    }
}
