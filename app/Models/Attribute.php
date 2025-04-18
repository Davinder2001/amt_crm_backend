<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;

class Attribute extends Model
{
    protected $fillable = ['name'];


    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    public function values()
    {
        return $this->hasMany(AttributeValue::class);
    }
}
