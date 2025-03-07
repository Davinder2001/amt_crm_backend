<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;

class BaseModel extends Model
{
    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope);
    }
}
