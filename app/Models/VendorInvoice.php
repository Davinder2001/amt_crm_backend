<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorInvoice extends Model
{
    protected $fillable = [
        'vendor_id',
        'invoice_no',
        'invoice_date',
    ];

    public function paymentHistories(): HasMany
    {
        return $this->hasMany(VendorPaymentHistory::class, 'vendor_invoice_id');
    }
}
