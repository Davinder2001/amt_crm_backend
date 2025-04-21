<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'items',
        'purchase_date',
        'details',
        'subtotal',
    ];

    protected $casts = [
        'items' => 'array',
        'purchase_date' => 'date',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
