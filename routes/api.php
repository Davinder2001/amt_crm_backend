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
    ItemTaxController,
    CreditManagementController,
    AdminManagementController,
    PredefinedTaskController,
    NotificationController,
    MessageController,
    QuotationController,
    PhonePeController,
    PackageController,
    BusinessCategoryController,
    AddNewCompanyController
};



// Version 1 API's
Route::prefix('v1')->group(function () {

    // Guest API's
    Route::middleware(['api'])->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('c-login', [AuthController::class, 'companyLogin']);
        Route::post('register', [AuthController::class, 'register']);
        Route::post('email-verification', [AuthController::class, 'mailVerification']);
        Route::post('verify-otp', [AuthController::class, 'verifyRegisterOtp']);
        
        
        Route::post('/admin-register', [AuthController::class, 'adminRegisterInitiate']);
        Route::post('/admin-register-confirm/{id}', [AuthController::class, 'adminRegisterConfirm']);
        Route::post('/send-wp-otp', [AuthController::class, 'sendWpOtp']);
        Route::post('/verify-wp-otp', [AuthController::class, 'veriWpfyOtp']);


        Route::post('password/forgot', [AuthController::class, 'sendResetOtp']);
        Route::post('password/verify-otp', [AuthController::class, 'verifyOtp']);
        Route::get('companies/names', [CompanyController::class, 'getAllCompanyNames']);

         Route::get('/all-packages', [PackageController::class, 'index']); 
         Route::get('/all-business-categories', [BusinessCategoryController::class, 'index']); 
    });


    // Protected Routes
    Route::middleware(['auth:sanctum', 'setActiveCompany'])->group(function () {


        //Super Admin Routes API's
        Route::middleware(['check.superadmin'])->group(function () {
            Route::get('/companies/pending', [CompanyController::class, 'getPendingCompanies']);
            Route::post('/companies/{id}/payment-verify', [CompanyController::class, 'verifyPayment']);
            Route::post('/companies/{id}/status-verify', [CompanyController::class, 'verifyStatus']);
            Route::get('/admins-management', [AdminManagementController::class, 'index']);
            Route::post('/admin-management/{id}/status', [AdminManagementController::class, 'updateStatus']);
            Route::get('/admins/{id}', [AdminManagementController::class, 'show']);
        });


        // Auth API's
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('password/change', [AuthController::class, 'resetPassword']);

        // Permissions API's
        Route::apiResource('permissions', PermissionController::class);

        // Roles & Role Permissions API's
        Route::get('roles', [RoleController::class, 'index'])->middleware('permission:view roles');
        Route::post('roles', [RoleController::class, 'store'])->middleware('permission:add roles');
        Route::get('roles/{id}', [RoleController::class, 'show'])->middleware('permission:view roles');
        Route::put('roles/{id}', [RoleController::class, 'update'])->middleware('permission:edit roles');
        Route::delete('roles/{id}', [RoleController::class, 'destroy'])->middleware('permission:delete roles');

        Route::prefix('roles/{id}')->group(function () {
            Route::post('assign-permission', [RolePermissionController::class, 'assignPermissionToRole']);
            Route::post('remove-permission', [RolePermissionController::class, 'removePermissionFromRole']);
        });

        // Role Permissions API's
        Route::prefix('users/{id}')->group(function () {
            Route::post('assign-role', [RolePermissionController::class, 'assignRole']);
            Route::post('remove-role', [RolePermissionController::class, 'removeRole']);
            Route::put('update-role', [RolePermissionController::class, 'updateRole']);
        });


        // Users API's
        Route::get('user', [UserController::class, 'authUser']);
        Route::get('users', [UserController::class, 'index'])->middleware('permission:view users');
        Route::post('users', [UserController::class, 'store'])->middleware('permission:add users');
        Route::get('users/{user}', [UserController::class, 'show'])->middleware('permission:view users');
        Route::put('users/{user}', [UserController::class, 'update'])->middleware('permission:edit users');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:delete users');





        // Tasks original API's
        Route::prefix('tasks')->group(function () {
            Route::post('/', [TaskController::class, 'store'])->middleware('permission:add task');
            Route::put('{id}', [TaskController::class, 'update'])->middleware('permission:update task');
            Route::delete('{id}', [TaskController::class, 'destroy'])->middleware('permission:delete task');
            Route::post('{id}/mark-working', [TaskController::class, 'markAsWorking']);
            Route::post('/history/{id}', [TaskHistoryController::class, 'store']);
            Route::get('/history/{id}', [TaskHistoryController::class, 'historyByTask']);
            Route::post('/{id}/end', [TaskController::class, 'endTask']);
            Route::get('/working', [TaskController::class, 'workingTask']);
        });

        Route::post('/tasks/{taskId}/set-reminder', [TaskController::class, 'setReminder']);
        Route::get('/tasks/{taskId}/reminder', [TaskController::class, 'viewReminder']);
        Route::put('/tasks/{taskId}/update-reminder', [TaskController::class, 'updateReminder']);





        // Tasks API's
        Route::prefix('tasks')->group(function () {
            Route::get('/pending', [TaskController::class, 'assignedPendingTasks']);
            Route::get('/all-history', [TaskHistoryController::class, 'allHistory']);
            Route::post('{id}/approve', [TaskHistoryController::class, 'approve']);
            Route::post('{id}/reject', [TaskHistoryController::class, 'reject']);
            Route::post('{id}/accept', [TaskHistoryController::class, 'acceptTask']);
            Route::get('/', [TaskController::class, 'index']);
            Route::get('{id}', [TaskController::class, 'show']);
        });


        // Task History API's
        Route::prefix('task-history')->group(function () {
            Route::post('{id}/approve', [TaskHistoryController::class, 'approve']);
            Route::post('{id}/reject', [TaskHistoryController::class, 'reject']);
        });








        // Employee Management API's
        Route::middleware(['injectUserType:employee'])->group(function () {
            Route::prefix('employee')->group(function () {
                Route::get('/', [EmployeeController::class, 'index'])->middleware('permission:view employee');
                Route::post('/', [EmployeeController::class, 'store'])->middleware('permission:add employee');
                Route::get('{id}', [EmployeeController::class, 'show'])->middleware('permission:view employee');
                Route::put('{id}', [EmployeeController::class, 'update'])->middleware('permission:update employee');
                Route::delete('{id}', [EmployeeController::class, 'destroy'])->middleware('permission:delete employee');
                Route::get('salarySlip/{id}', [SalaryController::class, 'salarySlip'])->middleware('permission:view employee salary');
                Route::get('downloadSlip/{id}', [SalaryController::class, 'downloadPdfSlip']);
            });
        });


        // Companies API's
        Route::prefix('companies')->group(function () {
            Route::get('/', [CompanyController::class, 'index'])->middleware('permission:view company');
            Route::post('/', [CompanyController::class, 'store'])->middleware('permission:add company');
            Route::get('{id}', [CompanyController::class, 'show'])->middleware('permission:view company');
            Route::put('{id}', [CompanyController::class, 'update'])->middleware('permission:update company');
            Route::delete('{id}', [CompanyController::class, 'destroy'])->middleware('permission:delete company');
        });

        // Selected Companies API's
        Route::get('selectedCompanies', [CompanyController::class, 'getSelectedCompanies']);
        Route::post('selectedCompanies/{id}', [CompanyController::class, 'selectedCompanies']);


        // Attendance API's
        Route::prefix('attendance')->group(function () {
            Route::get('/summary', [AttendanceController::class, 'getAttendanceSummary']);
            Route::get('/all', [AttendanceController::class, 'getAllAttendance']);
            Route::get('/my', [AttendanceController::class, 'myAttendance']);
            Route::post('/', [AttendanceController::class, 'recordAttendance']);
            Route::get('/{id}', [AttendanceController::class, 'getAttendance']);
            Route::put('/approve/{id}', [AttendanceController::class, 'approveAttendance']);
            Route::put('/reject/{id}', [AttendanceController::class, 'rejectAttendance']);
        });

        // Attendance and Leave Management API's
        Route::get('attendances', [AttendanceController::class, 'getAllAttendance']);
        Route::post('/apply-for-leave', [AttendanceController::class, 'applyForLeave']);


        // Salary API's
        Route::get('employees/salary', [SalaryController::class, 'index']);
        Route::get('employee/{id}/salary', [SalaryController::class, 'show']);
        Route::get('employee/{id}/salary-increment', [SalaryController::class, 'increment']);


        // Shifts API's
        Route::prefix('shifts')->group(function () {
            Route::get('/', [ShiftsController::class, 'index']);
            Route::post('/', [ShiftsController::class, 'store']);
            Route::put('/', [ShiftsController::class, 'update']);
            Route::get('{id}', [ShiftsController::class, 'show']);
        });


        // Store API's
        Route::prefix('store')->group(function () {
            // Items API's
            Route::post('add-items', [ItemsController::class, 'store']);
            Route::get('items', [ItemsController::class, 'index']);
            Route::get('items/{id}', [ItemsController::class, 'show']);
            Route::put('items/{id}', [ItemsController::class, 'update']);
            Route::delete('items/{id}', [ItemsController::class, 'destroy']);
            Route::post('bulk-items', [ItemsController::class, 'storeBulkItems']);
            Route::get('cat-items', [ItemsController::class, 'getItemCatTree']);

            // Vendors API's
            Route::get('vendors', [StoreVendorController::class, 'index']);
            Route::post('vendors', [StoreVendorController::class, 'store']);
            Route::get('vendors/{id}', [StoreVendorController::class, 'show']);
            Route::put('vendors/{id}', [StoreVendorController::class, 'update']);
            Route::delete('vendors/{id}', [StoreVendorController::class, 'destroy']);
        });

        // Catalog API's
        Route::prefix('catalog')->middleware('auth:sanctum')->group(function () {
            Route::get('/', [CatalogController::class, 'index']);
            Route::put('/add/{id}', [CatalogController::class, 'addToCatalog']);
            Route::put('/remove/{id}', [CatalogController::class, 'removeFromCatalog']);
        });

        // Invoice Management API's
        Route::prefix('invoices')->group(function () {


            // Due Payments Credit Management API's
            Route::prefix('credits')->group(function () {
                Route::get('/', [CreditManagementController::class, 'index']);
                Route::get('/users', [CreditManagementController::class, 'users']);
                Route::get('/{id}', [CreditManagementController::class, 'show']);
                Route::post('/{id}/pay', [CreditManagementController::class, 'closeDue']);
            });


            Route::get('/', [InvoicesController::class, 'index']);
            Route::get('/{id}', [InvoicesController::class, 'show']);
            Route::get('/{id}/download', [InvoicesController::class, 'download']);
            Route::post('/', [InvoicesController::class, 'store']);
            Route::post('/print', [InvoicesController::class, 'storeAndPrint']);
            Route::post('/mail', [InvoicesController::class, 'storeAndMail']);
            Route::post('/{id}/whatsapp', [InvoicesController::class, 'sendToWhatsapp']);
        });


        // Add as a vendor and OCR Scan API's
        Route::prefix('add-as-vendor')->group(function () {
            Route::post('/', [StoreVendorController::class, 'addAsVendor']);
            Route::post('/ocrscan', [ProductOcrController::class, 'scanAndSaveText']);
            Route::post('/save-items', [ProductOcrController::class, 'store']);
        });


        // Customer Management API's
        Route::prefix('customers')->group(function () {
            Route::get('/', [CustomerController::class, 'index']);
            Route::post('/', [CustomerController::class, 'store']);
            Route::get('{id}', [CustomerController::class, 'show']);
            Route::put('{id}', [CustomerController::class, 'update']);
            Route::delete('{id}', [CustomerController::class, 'destroy']);
        });


        // Attribute Management API's
        Route::prefix('attributes')->group(function () {
            Route::get('/', [AttributeController::class, 'index']);
            Route::post('/', [AttributeController::class, 'store']);
            Route::get('/{id}', [AttributeController::class, 'show']);
            Route::put('/{id}', [AttributeController::class, 'update']);
            Route::delete('/{id}', [AttributeController::class, 'destroy']);
            Route::patch('/{id}/toggle-status', [AttributeController::class, 'toggleStatus']);
        });

        // Attribute Variations API's
        Route::get('/variations', [AttributeController::class, 'variations']);

        // hr and dashboard management API's
        Route::prefix('hr')->controller(HRController::class)->group(function () {
            Route::get('/dashboard-summary', 'dashboardSummary');
            Route::get('/attendance-summary', 'attendanceSummary');
            Route::get('/early-departures', 'earlyDepartures');
            Route::get('/leave-summary', 'leaveSummary');
        });

        // Categories API's
        Route::prefix('categories')->group(function () {
            Route::get('/', [CategoryController::class, 'index']);
            Route::post('/', [CategoryController::class, 'store']);
            Route::get('/{id}', [CategoryController::class, 'show']);
            Route::put('/{id}', [CategoryController::class, 'update']);
            Route::delete('/{id}', [CategoryController::class, 'destroy']);
        });

        // Task Management API's
        Route::prefix('taxes')->group(function () {
            Route::get('/', [ItemTaxController::class, 'index']);
            Route::post('/', [ItemTaxController::class, 'store']);
            Route::get('/{tax}', [ItemTaxController::class, 'show']);
            Route::put('/{tax}', [ItemTaxController::class, 'update']);
            Route::delete('/{tax}', [ItemTaxController::class, 'destroy']);
        });

        // Predefined Tasks API's
        Route::prefix('predefined-tasks')->group(function () {
            Route::get('/', [PredefinedTaskController::class, 'index']);
            Route::get('/{id}', [PredefinedTaskController::class, 'show']);
            Route::post('/', [PredefinedTaskController::class, 'store']);
            Route::put('/{id}', [PredefinedTaskController::class, 'update']);
            Route::delete('/{id}', [PredefinedTaskController::class, 'destroy']);
        });

        // Notifications API's
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
            Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
        });

        // Chats API's
        Route::prefix('chats')->group(function () {
            Route::get('/', [MessageController::class, 'chats']);
            Route::post('/{id}/message', [MessageController::class, 'sendMessageToUser']);
            Route::get('/with-user/{id}', [MessageController::class, 'getChatWithUser']);
            Route::get('/users', [MessageController::class, 'chatUsers']);
            Route::delete('/message/{messageId}', [MessageController::class, 'deleteMessage']);
            Route::delete('/with-user/{id}', [MessageController::class, 'deleteAllChatsWithUser']);
        });

        // Qutation API's
        Route::prefix('quotations')->group(function () {
            Route::get('/', [QuotationController::class, 'index']);
            Route::post('/', [QuotationController::class, 'store']);
            Route::get('/{id}/pdf', [QuotationController::class, 'generatePdf']);
        });


        Route::prefix('add-new-company')->group(function () {
            Route::post('/', [AddNewCompanyController::class, 'store']); 
        });

        Route::prefix('pricing-packages')->group(function () {
            Route::get('/', [PackageController::class, 'index']); 
            Route::post('/', [PackageController::class, 'store']);
            Route::get('/{id}', [PackageController::class, 'show']); 
            Route::put('/{id}', [PackageController::class, 'update']);
            Route::patch('/{id}', [PackageController::class, 'update']);
            Route::delete('/{id}', [PackageController::class, 'destroy']);
        });


        Route::apiResource('business-categories', BusinessCategoryController::class);




    });
});

Route::post('/phonepe/pay', [PhonePeController::class, 'initiate']);
Route::post('/payment/callback', [PhonePeController::class, 'callback']);
