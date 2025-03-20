<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
     */
    public static function generateUid()
    {
        $lastUid = self::max('uid');

        if ($lastUid) {
            $lastNumber = (int) preg_replace('/\D/', '', $lastUid);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return 'AMT' . str_pad($newNumber, 10, '0', STR_PAD_LEFT);
    }

    /**
     * Many-to-Many relationship to fetch all companies the user is associated with.
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_user')
                    ->withPivot('role')
                    ->withTimestamps();
    }


    public function company()
    {
        return $this->belongsTo(Company::class);
    }


    /**
     * Fetch the role of the user in a specific company.
     */
    public function getCompanyRole($companyId)
    {
        return $this->companies()->where('company_id', $companyId)->first()?->pivot->role;
    }
}
