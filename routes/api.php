<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthController,
    UserController,
    RoleController,
    RolePermissionController,
    PermissionController,
    TaskController,
    EmployeeController,
    AttendanceController,
    CompanyController,
    SalaryController,
    ShiftsController,
    ItemsController,
    StoreVendorController,
    ProductOcrController,
    CatalogController,
    InvoicesController,
    CustomerController,
    AttributeController,
    HRController,
    CategoryController,
    TaskHistoryController,
    ItemTaxController
};




Route::prefix('v1')->group(function () {

    // Guest Routes
    Route::middleware(['api'])->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('c-login', [AuthController::class, 'companyLogin']);
        Route::post('register', [AuthController::class, 'register']);
        Route::post('admin-register', [AuthController::class, 'adminRegister']);
        Route::post('password/forgot', [AuthController::class, 'sendResetOtp']);
        Route::post('password/verify-otp', [AuthController::class, 'verifyOtp']);
    });

    // Protected Routes
    Route::middleware(['auth:sanctum', 'setActiveCompany'])->group(function () {

        // Auth
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('password/change', [AuthController::class, 'resetPassword']);

        // Permissions
        Route::apiResource('permissions', PermissionController::class);

        // Roles & Role Permissions
        Route::get('roles', [RoleController::class, 'index'])->middleware('permission:view roles');
        Route::post('roles', [RoleController::class, 'store'])->middleware('permission:add roles');
        Route::get('roles/{id}', [RoleController::class, 'show'])->middleware('permission:view roles');
        Route::put('roles/{id}', [RoleController::class, 'update'])->middleware('permission:edit roles');
        Route::delete('roles/{id}', [RoleController::class, 'destroy'])->middleware('permission:delete roles');

        Route::prefix('roles/{id}')->group(function () {
            Route::post('assign-permission', [RolePermissionController::class, 'assignPermissionToRole']);
            Route::post('remove-permission', [RolePermissionController::class, 'removePermissionFromRole']);
        });

        Route::prefix('users/{id}')->group(function () {
            Route::post('assign-role', [RolePermissionController::class, 'assignRole']);
            Route::post('remove-role', [RolePermissionController::class, 'removeRole']);
            Route::put('update-role', [RolePermissionController::class, 'updateRole']);
        });

        // Users
        Route::get('user', [UserController::class, 'authUser']);
        Route::get('users', [UserController::class, 'index'])->middleware('permission:view users');
        Route::post('users', [UserController::class, 'store'])->middleware('permission:add users');
        Route::get('users/{user}', [UserController::class, 'show'])->middleware('permission:view users');
        Route::put('users/{user}', [UserController::class, 'update'])->middleware('permission:edit users');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:delete users');

        // Tasks
        // Route::prefix('tasks')->middleware('permission:view task')->group(function () {
        Route::prefix('tasks')->group(function () {
            Route::get('/', [TaskController::class, 'index']);
            Route::get('{id}', [TaskController::class, 'show']);
            Route::post('/history/{id}', [TaskHistoryController::class, 'store']);
            Route::get('/history/{id}', [TaskHistoryController::class, 'historyByTask']);        
            Route::post('{id}/approve', [TaskHistoryController::class, 'approve']);
            Route::post('{id}/reject', [TaskHistoryController::class, 'reject']);
        });
        Route::get('/all-history', [TaskHistoryController::class, 'allHistory']);        
        
        
        Route::prefix('task-history')->group(function () {
            Route::post('{id}/approve', [TaskHistoryController::class, 'approve']);
            Route::post('{id}/reject', [TaskHistoryController::class, 'reject']);
        });

        Route::prefix('tasks')->group(function () {
            Route::post('/', [TaskController::class, 'store'])->middleware('permission:add task');
            Route::put('{id}', [TaskController::class, 'update'])->middleware('permission:update task');
            Route::delete('{id}', [TaskController::class, 'destroy'])->middleware('permission:delete task');
        });

        // Employee Management
        Route::middleware(['injectUserType:employee'])->group(function () {
            Route::prefix('employee')->group(function () {
                Route::get('/', [EmployeeController::class, 'index'])->middleware('permission:view employee');
                Route::post('/', [EmployeeController::class, 'store'])->middleware('permission:add employee');
                Route::get('{id}', [EmployeeController::class, 'show'])->middleware('permission:view employee');
                Route::put('{id}', [EmployeeController::class, 'update'])->middleware('permission:update employee');
                Route::delete('{id}', [EmployeeController::class, 'destroy'])->middleware('permission:delete employee');
                Route::get('salarySlip/{id}', [EmployeeController::class, 'salarySlip'])->middleware('permission:view employee salary');
                Route::get('downloadSlip/{id}', [EmployeeController::class, 'downloadPdfSlip']);
            });
        });

        // Companies
        Route::prefix('companies')->group(function () {
            Route::get('/', [CompanyController::class, 'index'])->middleware('permission:view company');
            Route::post('/', [CompanyController::class, 'store'])->middleware('permission:add company');
            Route::get('{id}', [CompanyController::class, 'show'])->middleware('permission:view company');
            Route::put('{id}', [CompanyController::class, 'update'])->middleware('permission:update company');
            Route::delete('{id}', [CompanyController::class, 'destroy'])->middleware('permission:delete company');
        });

        
        Route::get('selectedCompanies', [CompanyController::class, 'getSelectedCompanies']);
        Route::post('selectedCompanies/{id}', [CompanyController::class, 'selectedCompanies']);


        // Attendance
        Route::prefix('attendance')->group(function () {
            Route::post('/', [AttendanceController::class, 'recordAttendance']);
            Route::get('/{id}', [AttendanceController::class, 'getAttendance']);
            Route::get('/all', [AttendanceController::class, 'getAllAttendance']);
            Route::put('/approve/{id}', [AttendanceController::class, 'approveAttendance']);
            Route::put('/reject/{id}', [AttendanceController::class, 'rejectAttendance']);
        });
        
        Route::get('attendances', [AttendanceController::class, 'getAllAttendance']);



        Route::post('/apply-for-leave', [AttendanceController::class, 'applyForLeave']);

        // Salary
        Route::get('employees/salary', [SalaryController::class, 'index']);
        Route::get('employee/{id}/salary', [SalaryController::class, 'show']);
        Route::get('employee/{id}/salary-increment', [SalaryController::class, 'increment']);

        // Shifts
        Route::prefix('shifts')->group(function () {
            Route::get('/', [ShiftsController::class, 'index']);
            Route::post('/', [ShiftsController::class, 'store']);
            Route::put('/', [ShiftsController::class, 'update']);
            Route::get('{id}', [ShiftsController::class, 'show']);
        });

        // Store
        Route::prefix('store')->group(function () {
            // Items
            Route::post('add-items', [ItemsController::class, 'store']);
            Route::get('items', [ItemsController::class, 'index']);
            Route::get('items/{id}', [ItemsController::class, 'show']);
            Route::put('items/{id}', [ItemsController::class, 'update']);
            Route::delete('items/{id}', [ItemsController::class, 'destroy']);
            Route::post('bulk-items', [ItemsController::class, 'storeBulkItems']);
            Route::get('cat-items', [ItemsController::class, 'getItemCatTree']);

            // Vendors
            Route::get('vendors', [StoreVendorController::class, 'index']);
            Route::post('vendors', [StoreVendorController::class, 'store']);
            Route::get('vendors/{id}', [StoreVendorController::class, 'show']);
            Route::put('vendors/{id}', [StoreVendorController::class, 'update']);
            Route::delete('vendors/{id}', [StoreVendorController::class, 'destroy']);
           

        });


        Route::prefix('catalog')->middleware('auth:sanctum')->group(function () {
            Route::get('/', [CatalogController::class, 'index']);
            Route::put('/add/{id}', [CatalogController::class, 'addToCatalog']);
            Route::put('/remove/{id}', [CatalogController::class, 'removeFromCatalog']);
        });
        
        Route::prefix('invoices')->group(function () {
            Route::get('/', [InvoicesController::class, 'index']);
            Route::get('/{id}', [InvoicesController::class, 'show']);
            Route::get('/{id}/download', [InvoicesController::class, 'download']);
            Route::post('/', [InvoicesController::class, 'store']);
            Route::post('/print', [InvoicesController::class, 'storeAndPrint']);
            Route::post('/mail', [InvoicesController::class, 'storeAndMail']);
        });
    


        Route::prefix('add-as-vendor')->group(function () {
            Route::post('/', [StoreVendorController::class, 'addAsVendor']);
            Route::post('/ocrscan', [ProductOcrController::class, 'scanAndSaveText']);
            Route::post('/save-items', [ProductOcrController::class, 'store']);
        });


        Route::prefix('customers')->group(function () {
            Route::get('/', [CustomerController::class, 'index']);
            Route::post('/', [CustomerController::class, 'store']);
            Route::get('{id}', [CustomerController::class, 'show']);
            Route::put('{id}', [CustomerController::class, 'update']);
            Route::delete('{id}', [CustomerController::class, 'destroy']); 
        });


                
        Route::prefix('attributes')->group(function () {
            Route::get('/', [AttributeController::class, 'index']);
            Route::post('/', [AttributeController::class, 'store']);
            Route::get('/{id}', [AttributeController::class, 'show']);
            Route::put('/{id}', [AttributeController::class, 'update']);
            Route::delete('/{id}', [AttributeController::class, 'destroy']);
            Route::patch('/{id}/toggle-status', [AttributeController::class, 'toggleStatus']);
        });

        Route::get('/variations', [AttributeController::class, 'variations']);

        Route::prefix('hr')->controller(HRController::class)->group(function () {
            Route::get('/dashboard-summary', 'dashboardSummary');
            Route::get('/attendance-summary', 'attendanceSummary');
            Route::get('/early-departures', 'earlyDepartures');
            Route::get('/leave-summary', 'leaveSummary');
        });


        Route::prefix('categories')->group(function () {
            Route::get('/', [CategoryController::class, 'index']);
            Route::post('/', [CategoryController::class, 'store']);
            Route::get('/{id}', [CategoryController::class, 'show']);
            Route::put('/{id}', [CategoryController::class, 'update']);
            Route::delete('/{id}', [CategoryController::class, 'destroy']);
        });


        Route::prefix('taxes')->group(function () {
            Route::get('/', [ItemTaxController::class, 'index']);
            Route::post('/', [ItemTaxController::class, 'store']);
            Route::get('/{tax}', [ItemTaxController::class, 'show']);
            Route::put('/{tax}', [ItemTaxController::class, 'update']);
            Route::delete('/{tax}', [ItemTaxController::class, 'destroy']);
        });

        
    });
});
