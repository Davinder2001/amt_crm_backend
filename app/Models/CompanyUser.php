<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;

class CompanyUser extends Model
{
    use HasFactory;

    protected $table = 'company_user';

    protected $fillable = [
        'user_id',
        'company_id',
        'user_type',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    // protected static function booted()
    // {
    //     static::addGlobalScope(new CompanyScope());
    // }

    /**
     * The attributes that should be cast to native types.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The attributes that should be cast to native types.
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
    
}