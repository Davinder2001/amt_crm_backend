<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Company;
use App\Models\Scopes\CompanyScope;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
        'number', // Added mobile number field
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    /**
     * Boot the model and apply the CompanyScope globally.
     */
    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope);
    }

    /**
     * Relationship with the Company model.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    /**
     * Accessor for company slug.
     */
    public function getCompanySlugAttribute(): ?string
    {
        return $this->company->company_slug ?? null;
    }

    /**
     * Accessor for company name.
     */
    public function getCompanyNameAttribute(): ?string
    {
        return $this->company->company_name ?? null;
    }
}
