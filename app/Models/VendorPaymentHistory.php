<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPaymentHistory extends Model
{
    protected $fillable = [
        'vendor_invoice_id',
        'payment_method',
        'credit_payment_type',
        'partial_amount',
        'amount_paid',
        'payment_date',
        'note',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(VendorInvoice::class, 'vendor_invoice_id');
    }
}
