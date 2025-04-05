<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'client_name',
        'client_email',
        'invoice_date',
        'total_amount',
        'pdf_path',
    ];

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
