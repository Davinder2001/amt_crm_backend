<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RolePermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\API\PermissionController;
use App\Http\Controllers\API\TaskController;
use App\Http\Controllers\API\EmployeeController;

Route::prefix('v1')->group(function () {


    // **Public API Routes (No Authentication Required)**
    Route::middleware(['api'])->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('c-login', [AuthController::class, 'companyLogin']);
        Route::post('register', [AuthController::class, 'register']);
        Route::post('admin-register', [AuthController::class, 'adminRegister']);
    });

    
    // **Protected Routes (Require Sanctum Authentication)**
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::apiResource('permissions', PermissionController::class);


        // **Role & Permission Management**
        Route::post('users/{id}/assign-role', [RolePermissionController::class, 'assignRole']);
        Route::post('users/{id}/remove-role', [RolePermissionController::class, 'removeRole']);
        Route::put('users/{id}/update-role', [RolePermissionController::class, 'updateRole']);
        Route::post('roles/{id}/assign-permission', [RolePermissionController::class, 'assignPermissionToRole']);
        Route::post('roles/{id}/remove-permission', [RolePermissionController::class, 'removePermissionFromRole']);


        // **User Management**
        Route::get('user', [UserController::class, 'authUser']);
        Route::get('users', [UserController::class, 'index'])->middleware('permission:View User');
        Route::post('users', [UserController::class, 'store']);
        Route::get('users/{user}', [UserController::class, 'show']);
        Route::put('users/{user}', [UserController::class, 'update']);
        Route::delete('users/{user}', [UserController::class, 'destroy']);


        // **Role Management**
        Route::get('roles', [RoleController::class, 'index']);
        Route::post('roles', [RoleController::class, 'store']);
        Route::get('roles/{role}', [RoleController::class, 'show']);
        Route::put('roles/{role}', [RoleController::class, 'update']);
        Route::delete('roles/{role}', [RoleController::class, 'destroy']);


        // **Task Management**
        Route::post('tasks', [TaskController::class, 'store']);
        Route::get('tasks', [TaskController::class, 'index']);
        Route::get('tasks/{id}', [TaskController::class, 'show']);
        Route::put('tasks/{id}', [TaskController::class, 'update']);
        Route::delete('tasks/{id}', [TaskController::class, 'destroy']);

        
        // **Task Management**
        Route::get('employees', [EmployeeController::class, 'index']);
        Route::post('employees', [EmployeeController::class, 'store']);
        Route::get('employees/{id}', [EmployeeController::class, 'show']);
        Route::put('employees/{id}', [EmployeeController::class, 'update']);
        Route::delete('employees/{id}', [EmployeeController::class, 'destroy']);
        
    });
    

});
