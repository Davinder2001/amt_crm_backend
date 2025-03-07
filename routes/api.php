<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RolePermissionController;
use App\Http\Controllers\Api\RoleController;



Route::prefix('v1')->group(function () {

    // Public routes (no session needed)
    Route::middleware(['api'])->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('c-login', [AuthController::class, 'companyLogin']);
        Route::post('register', [AuthController::class, 'register']);
        Route::post('admin-register', [AuthController::class, 'adminRegister']);
    });


    // Protected routes (using Sanctum only)
    Route::middleware(['web', 'auth:sanctum'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);


        Route::post('users/{id}/assign-role', [RolePermissionController::class, 'assignRole']);
        Route::post('users/{id}/remove-role', [RolePermissionController::class, 'removeRole']);
        Route::put('users/{id}/update-role', [RolePermissionController::class, 'updateRole']);
        Route::post('roles/{roleName}/assign-permission', [RolePermissionController::class, 'assignPermissionToRole']);
        Route::post('roles/{roleName}/remove-permission', [RolePermissionController::class, 'removePermissionFromRole']);



        Route::get('user', [UserController::class, 'authUser']);
        Route::apiResource('users', UserController::class);
        Route::apiResource('roles', RoleController::class);
    });

});
