<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\SetActiveCompany;
use App\Http\Middleware\InjectUserType;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckSuperAdmin;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'permission'        => CheckPermission::class,
            'setActiveCompany'  => SetActiveCompany::class,
            'injectUserType'    => InjectUserType::class,
            'auth'              => Authenticate::class,
            'check.superadmin'  => CheckSuperAdmin::class,
        ]);

        $middleware->api([
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,           
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
