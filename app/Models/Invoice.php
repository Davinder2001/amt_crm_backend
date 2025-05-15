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
        'discount_amount',
        'discount_percentage',
        'final_amount',
        'issued_by',
        'payment_method',      
        'pdf_path',
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
     * The attributes that should be cast to native types.
     */
    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }
    
    /**
     * The attributes that should be cast to native types.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * The attributes that should be cast to native types.
     */
    public function credit()
    {
        return $this->hasOne(CustomerCredit::class);
    }

}
