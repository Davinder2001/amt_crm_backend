<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name'
    ];

    public function storeItems()
    {
        return $this->belongsToMany(Item::class, 'category_item');
    }
}
