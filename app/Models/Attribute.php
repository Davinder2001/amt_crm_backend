<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;

class Attribute extends Model
{
    protected $fillable = [
        'name',
        'company_id'
    ];

    /*
     * The attributes that should be cast to native types.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    /**
     * The attributes that should be cast to native types.
     */
    public function values()
    {
        return $this->hasMany(AttributeValue::class);
    }
}
