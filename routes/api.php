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
    HRController
};





use Illuminate\Support\Facades\Mail;


Route::get('/test-mail', function () {
    try {
        Mail::raw('This is a test email from Laravel live server.', function ($message) {
            $message->to('panku102001@gmail.com')
                    ->subject('Laravel Mail Test');
        });

        return response()->json([
            'status' => true,
            'message' => 'Test email sent successfully.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Mail sending failed.',
            'error' => $e->getMessage()
        ], 500);
    }
});


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
        Route::get('roles/{role}', [RoleController::class, 'show'])->middleware('permission:view roles');
        Route::put('roles/{role}', [RoleController::class, 'update'])->middleware('permission:edit roles');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:delete roles');

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
        Route::prefix('tasks')->middleware('permission:view task')->group(function () {
            Route::get('/', [TaskController::class, 'index']);
            Route::get('{id}', [TaskController::class, 'show']);
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
            Route::post('/', [InvoicesController::class, 'store']);
            Route::get('/{id}', [InvoicesController::class, 'show']);
            Route::get('/{id}/download', [InvoicesController::class, 'download']);
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

        Route::prefix('hr')->controller(HRController::class)->group(function () {
            Route::get('/dashboard-summary', 'dashboardSummary');
            Route::get('/attendance-summary', 'attendanceSummary');
            Route::get('/early-departures', 'earlyDepartures');
            Route::get('/leave-summary', 'leaveSummary');
        });
        
    });
});
