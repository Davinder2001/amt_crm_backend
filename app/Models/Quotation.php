<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;


class Quotation extends Model
{
    protected $fillable = [
        'customer_name',
        'items',
        'user_id',
    ];

    protected $casts = [
        'items' => 'array',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope());
    }
}
