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
        'tax_percentage',
        'tax_amount',
        'total',
    ];


    /**
     * The attributes that should be cast to native types.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * The attributes that should be cast to native types.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }
    
    /**
     * The specific variant sold (if any).
     */
    public function variant()
    {
        return $this->belongsTo(ItemVariant::class, 'variant_id');
    }
}