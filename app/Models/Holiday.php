<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'company_id',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
