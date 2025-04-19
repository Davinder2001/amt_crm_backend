<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Scopes\CompanyScope;

class Tax extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'rate'
    ];


    /**
     * Automatically apply company scope to all queries.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    public function items()
    {
        return $this->belongsToMany(Item::class, 'item_tax');
    }
}
