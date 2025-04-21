<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemVariant extends Model
{
    protected $fillable = [
        'item_id',
        'price',
        'stock',
        'images'
    ];

    protected $casts = [
        'images' => 'array',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * The attributes that should be cast to native types.
     */
    public function attributeValues()
    {
        return $this->belongsToMany(AttributeValue::class, 'item_variant_attribute_value')->withPivot('attribute_id')->withTimestamps();
    }
}
