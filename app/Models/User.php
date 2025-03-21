<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Services\UserUidService;
use \App\Models\UserMeta;
use App\Models\Scopes\CompanyScope;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles;

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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->uid)) {
                $user->uid = UserUidService::generateNewUid();
            }
        });
    }


    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_user')
                    ->withPivot('user_type')
                    ->withTimestamps();
    }


    // public function companies()
    // {
    //     return $this->belongsToMany(Company::class);
    // }

    public function meta()
    {
        return $this->hasMany(UserMeta::class);
    }

}
