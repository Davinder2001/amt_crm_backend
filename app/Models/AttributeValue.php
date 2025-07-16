<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeValue extends Model
{
    protected $fillable = [
        'attribute_id', 
        'value'
    ];

    /**
     * The attributes that should be cast to native types.
     */
    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    /**
     * The item variants that belong to the attribute value.
     */
    public function itemVariants()
    {
        return $this->belongsToMany(ItemVariant::class, 'item_variant_attribute_value')->withPivot('attribute_id')->withTimestamps();
    }
}
