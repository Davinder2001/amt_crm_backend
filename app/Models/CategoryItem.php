<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CategoryItem extends Model
{
    use HasFactory;

    protected $table = 'category_item';
    public $timestamps = false;

    protected $fillable = [
        'store_item_id',
        'category_id',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'store_item_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
