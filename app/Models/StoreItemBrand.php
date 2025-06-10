<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
     * Get the company that owns the store item brand.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}