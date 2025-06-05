<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';
    
    protected $fillable = [
        'user_id', 
        'order_id',
        'transaction_id',
        'payment_status',
        'payment_method',
        'payment_reason',
        'payment_fail_reason',
        'transaction_amount',
        'payment_date',
        'payment_time',
        'refund',
        'refund_reason',
    ];
}
