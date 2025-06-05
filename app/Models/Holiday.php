<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;


class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'company_id',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
        protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

}
