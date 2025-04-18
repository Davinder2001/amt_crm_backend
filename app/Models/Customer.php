<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'number',
        'email',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope());
    }
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
