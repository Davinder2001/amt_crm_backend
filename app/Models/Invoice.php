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
        'client_phone',
        'invoice_date',
        'total_amount',
        'sub_total',
        'service_charge_amount',
        'service_charge_percent',
        'service_charge_gst',
        'service_charge_final',
        'discount_amount',
        'discount_percentage',
        'delivery_address',
        'delivery_pincode',
        'delivery_charge',
        'final_amount',
        'payment_method',
        'issued_by',
        'issued_by_name',
        'pdf_path',
        'credit_note',
        'company_id',
        'sent_on_whatsapp',
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

    public function credit()
    {
        return $this->hasOne(CustomerCredit::class);
    }
}
