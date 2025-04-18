<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;

class Invoice extends Model
{
    protected $table = 'invoices';

    protected $fillable = [
        'invoice_number',
        'client_name',
        'client_email',
        'invoice_date',
        'total_amount',
        'discount_amount',
        'discount_percentage',
        'final_amount',
        'issued_by',
        'payment_method',      
        'pdf_path',
        'company_id',
    ];


    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
