<?php
// app/Models/Tax.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;

class Tax extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'rate',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }


    public function items()
    {
        return $this->belongsToMany( Item::class, 'item_tax', 'tax_id', 'store_item_id' )->withTimestamps();
    }
}
