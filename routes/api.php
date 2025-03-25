<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RolePermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\SalaryController;
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
    Route::middleware(['auth:sanctum', 'setActiveCompany'])->group(function () {
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
        Route::get('tasks', [TaskController::class, 'index'])->middleware('permission:view task');
        Route::post('tasks', [TaskController::class, 'store'])->middleware('permission:add task');
        Route::get('tasks/{id}', [TaskController::class, 'show'])->middleware('permission:view task');
        Route::put('tasks/{id}', [TaskController::class, 'update'])->middleware('permission:update task');
        Route::delete('tasks/{id}', [TaskController::class, 'destroy'])->middleware('permission:delete task');

        
        // **Task Management** //
        Route::get('employee', [EmployeeController::class, 'index'])->middleware('permission:view employee');
        Route::post('employee', [EmployeeController::class, 'store'])->middleware('permission:add employee');
        Route::get('employee/{id}', [EmployeeController::class, 'show'])->middleware('permission:view employee');
        Route::put('employee/{id}', [EmployeeController::class, 'update'])->middleware('permission:update employee');
        Route::delete('employee/{id}', [EmployeeController::class, 'destroy'])->middleware('permission:delete employee');
        Route::get('employee/salarySlip/{id}', [EmployeeController::class, 'salarySlip'])->middleware('permission:view employee salary');
        Route::get('employee/salary-slip-pdf/{id}', [EmployeeController::class, 'downloadSalarySlipPdf'])->middleware('permission:download employee salary');

        

       // ** Companey Management **//
        Route::get('companies', [CompanyController::class, 'index'])->middleware('permission:view company');
        Route::get('companies/{id}', [CompanyController::class, 'show'])->middleware('permission:view company');
        Route::post('companies', [CompanyController::class, 'store'])->middleware('permission:add company');
        Route::put('companies/{id}', [CompanyController::class, 'update'])->middleware('permission:update company');
        Route::delete('companies/{id}', [CompanyController::class, 'destroy'])->middleware('permission:delete company');
        Route::get('selectedCompanies', [CompanyController::class, 'getSelectedCompanies']);
        Route::post('selectedCompanies/{id}', [CompanyController::class, 'selectedCompanies']);


        Route::post('/attendance', [AttendanceController::class, 'recordAttendance']);
        Route::get('/attendance/{id}', [AttendanceController::class, 'getAttendance']);
        Route::get('/attendances', [AttendanceController::class, 'getAllAttendance']);

        Route::get('employees/salary', [SalaryController::class, 'index']);
        Route::get('employee/{id}/salary', [SalaryController::class, 'show']);
        Route::get('employee/{id}/salary-increment', [SalaryController::class, 'increment']);

    
        
    });
});