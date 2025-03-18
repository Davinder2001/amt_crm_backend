<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Company;
use App\Models\UserMeta;
use App\Models\Scopes\CompanyScope;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
        'number',
        'user_type',
        'uid',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    // Eager load meta data automatically with user queries
    protected $with = ['meta'];

    /**
     * Boot the model and add the CompanyScope to all queries.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope());
    }

    /**
     * Relationship with the Company model where the user belongs to a company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    /**
     * Relationship with roles (Spatie package).
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class, 'role_id');
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

    /**
     * Relationship with UserMeta model (One user can have multiple meta entries).
     */
    public function meta(): HasMany
    {
        return $this->hasMany(UserMeta::class);
    }

    /**
     * Retrieve a specific meta value for a user.
     */
    public function getMetaValue($key, $default = null)
    {
        $meta = $this->meta->firstWhere('meta_key', $key);
        return $meta ? $meta->meta_value : $default;
    }

    /**
     * Static method to generate a unique UID.
     * This method retrieves the current maximum UID, extracts its numeric part,
     * increments it, and returns a formatted UID string (e.g., AMT0000000001).
     *
     * @return string
     */
    public static function generateUid()
    {
        $lastUid = self::max('uid');
        
        if ($lastUid) {
            // Extract numeric part from the UID (remove any non-digit characters)
            $lastNumber = (int) preg_replace('/\D/', '', $lastUid);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        // Format the UID with the prefix 'AMT' and pad the number to 10 digits
        return 'AMT' . str_pad($newNumber, 10, '0', STR_PAD_LEFT);
    }

    /**
     * Relationship to get all companies where the user is the admin.
     *
     * This is the relationship you need for your login response.
     */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'admin_id', 'id');
    }
}
