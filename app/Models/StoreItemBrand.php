<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;

class StoreItemBrand extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'company_id',
    ];


    /**
     * The attributes that should be cast to native types.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }


    /**
     * Get the company that owns the store item brand.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
