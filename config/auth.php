<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | The default authentication guard is set to `api`, which uses JWT.
    |
    */



    'defaults' => [
        'guard' => 'api', // JWT for all users (admin, super-admin, user)
        'passwords' => 'users',
    ],



    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Define JWT-based authentication guards for different user roles.
    |
    */



    'guards' => [
        'api' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],
        'admin' => [
            'driver' => 'jwt',
            'provider' => 'admins',
        ],
        'super-admin' => [
            'driver' => 'jwt',
            'provider' => 'super-admin',
        ],
    ],



    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | Defines how users, admins, and super-admins are retrieved dynamically.
    |
    */



    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],
        'super-admin' => [
            'driver' => 'eloquent',
            'model' => App\Models\SuperAdmin::class,
        ],
    ],



    /*
    |--------------------------------------------------------------------------
    | Default Roles for Different Models
    |--------------------------------------------------------------------------
    |
    | These roles are assigned dynamically based on the model used.
    |
    */



    'roles' => [
        'super-admin' => [
            'model' => App\Models\SuperAdmin::class,
            'permissions' => ['*'], // Full access
        ],
        'admin' => [
            'model' => App\Models\Admin::class,
            'permissions' => ['manage-users', 'view-reports'],
        ],
        'user' => [
            'model' => App\Models\User::class,
            'permissions' => ['view-dashboard'],
        ],
    ],



    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | Password reset settings for users, admins, and super-admins.
    |
    */



    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
        'admins' => [
            'provider' => 'admins',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
        'super-admin' => [
            'provider' => 'super-admin',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],



    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Defines how long before users need to re-enter their password.
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
