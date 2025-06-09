<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;


class Leave extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'frequency',
        'type',
        'count',
    ];


    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }
}
