<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;

class Quotation extends Model
{
    protected $fillable = [
        'customer_name',
        'customer_number',
        'customer_email',
        'items',
        'tax_percent',
        'tax_amount',
        'service_charges',
        'sub_total',
        'total',
        'user_id',
        'company_id',
        'company_name' 
    ];

    protected $casts = [
        'items' => 'array',
        'tax_percent' => 'float',
        'tax_amount' => 'float',
        'service_charges' => 'float',
        'total' => 'float',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope());
    }

    // Relationships (if needed)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
