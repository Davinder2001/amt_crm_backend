<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RolePermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\API\PermissionController;
use App\Http\Controllers\API\TaskController;
use App\Http\Controllers\API\EmployeeController;
use App\Http\Controllers\API\CompanyController;
use Illuminate\Session\Middleware\StartSession;


Route::prefix('v1')->group(function () {



    Route::middleware(['api', StartSession::class])->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('c-login', [AuthController::class, 'companyLogin']);
        Route::post('register', [AuthController::class, 'register']);
        Route::post('admin-register', [AuthController::class, 'adminRegister']);
        Route::post('/password/forgot', [AuthController::class, 'sendResetOtp']);
        Route::post('/password/verify-otp', [AuthController::class, 'verifyOtp']);
    });
    
    
    
    
    // **Protected Routes (Require Sanctum Authentication)** //
    Route::middleware(['web' , 'auth:sanctum'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::apiResource('permissions', PermissionController::class);
        Route::post('/password/change', [AuthController::class, 'resetPassword']);


        // **Role & Permission Management** //
        Route::post('users/{id}/assign-role', [RolePermissionController::class, 'assignRole']);
        Route::post('users/{id}/remove-role', [RolePermissionController::class, 'removeRole']);
        Route::put('users/{id}/update-role', [RolePermissionController::class, 'updateRole']);
        Route::post('roles/{id}/assign-permission', [RolePermissionController::class, 'assignPermissionToRole']);
        Route::post('roles/{id}/remove-permission', [RolePermissionController::class, 'removePermissionFromRole']);


        // **User Management** //
        Route::get('user', [UserController::class, 'authUser']);
        Route::get('users', [UserController::class, 'index'])->middleware('permission:view users');
        Route::post('users', [UserController::class, 'store'])->middleware('permission:add users');
        Route::get('users/{user}', [UserController::class, 'show'])->middleware('permission:view users');
        Route::put('users/{user}', [UserController::class, 'update'])->middleware('permission:edit users');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:delete users');

        // **Role Management** //
        Route::get('roles', [RoleController::class, 'index'])->middleware('permission:view roles');
        Route::post('roles', [RoleController::class, 'store'])->middleware('permission:add roles');
        Route::get('roles/{role}', [RoleController::class, 'show'])->middleware('permission:view roles');
        Route::put('roles/{role}', [RoleController::class, 'update'])->middleware('permission:edit roles');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:delete roles');


        // **Task Management** //
        Route::post('tasks', [TaskController::class, 'store']);
        Route::get('tasks', [TaskController::class, 'index']);
        Route::get('tasks/{id}', [TaskController::class, 'show']);
        Route::put('tasks/{id}', [TaskController::class, 'update']);
        Route::delete('tasks/{id}', [TaskController::class, 'destroy']);

        
        // **Task Management**
        Route::get('employee', [EmployeeController::class, 'index']);
        Route::post('employee', [EmployeeController::class, 'store']);
        Route::get('employee/{id}', [EmployeeController::class, 'show']);
        Route::put('employee/{id}', [EmployeeController::class, 'update']);
        Route::delete('employee/{id}', [EmployeeController::class, 'destroy']);
        
    });
    

        Route::get('companies', [CompanyController::class, 'index']);
        Route::get('companies/{id}', [CompanyController::class, 'show']);
        Route::post('companies', [CompanyController::class, 'store']);
        Route::put('companies/{id}', [CompanyController::class, 'update']);
        Route::delete('companies/{id}', [CompanyController::class, 'destroy']);

});