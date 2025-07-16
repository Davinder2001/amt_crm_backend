<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;

class CustomerCredit extends Model
{
    protected $fillable = [
        'customer_id',
        'invoice_id',
        'total_due',
        'amount_paid',
        'outstanding',
        'company_id',
        'status',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
