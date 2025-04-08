<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'total',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

}
