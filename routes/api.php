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
    PackageController,
    BusinessCategoryController,
    AddNewCompanyController,
    TableColumnController,
    AdminAndCompanyRegisterController,
    LeavesAndHolidayController,
    PaymentAndBillingController,
    StoreItemBrandController,
    BulkActionsController,
    MeasuringUnitController,
    ItemStockController,
    ExpenseController
};



// Version 1 API's
Route::prefix('v1')->group(function () {


    // Guest API's
    Route::middleware(['api'])->group(function () {

        Route::post('/send-admin-otp', [AdminAndCompanyRegisterController::class, 'sendOtp']);
        Route::post('/register-admin', [AdminAndCompanyRegisterController::class, 'register']);





        Route::post('login', [AuthController::class, 'login']);
        Route::post('c-login', [AuthController::class, 'companyLogin']);
        Route::post('register', [AuthController::class, 'register']);
        Route::post('email-verification', [AuthController::class, 'mailVerification']);
        Route::post('verify-otp', [AuthController::class, 'verifyRegisterOtp']);



        Route::post('password/forgot', [AuthController::class, 'sendResetOtp']);
        Route::post('password/verify-otp', [AuthController::class, 'verifyOtp']);
        Route::get('companies/names', [CompanyController::class, 'getAllCompanyNames']);

        Route::get('/all-packages', [PackageController::class, 'index']);
        Route::get('/all-business-categories', [BusinessCategoryController::class, 'index']);
    });


    // Protected Routes
    Route::middleware(['auth:sanctum', 'setActiveCompany'])->group(function () {

        // Add company new apis
        Route::prefix('/add-company')->group(function () {
            Route::post('/pay', [AdminAndCompanyRegisterController::class, 'addNewCompanyPay']);
            Route::post('/{id}', [AdminAndCompanyRegisterController::class, 'createCompany']);
        });




        Route::middleware(['check.superadmin'])->group(function () {
            //Super Admin Routes API's

            Route::get('/payments/refunds', [PaymentAndBillingController::class, 'getRefundRequests']);
            Route::post('/payments/refund-approve/{transaction_id}', [PaymentAndBillingController::class, 'approveRefundRequest']);
            Route::post('/payments/refund-complete/{transaction_id}', [PaymentAndBillingController::class, 'markRefunded']);
            Route::post('/payments/refund-decline/{transaction_id}', [PaymentAndBillingController::class, 'declineRefundRequest']);


            Route::get('/companies/pending', [CompanyController::class, 'getPendingCompanies']);
            Route::post('/companies/{id}/payment-status', [CompanyController::class, 'paymentStatus']);
            Route::post('/companies/{id}/verification-status', [CompanyController::class, 'verificationStatus']);
            Route::get('/admins-management', [AdminManagementController::class, 'index']);
            Route::post('/admin-management/{id}/status', [AdminManagementController::class, 'updateStatus']);
            Route::get('/admins/{id}', [AdminManagementController::class, 'show']);



            // Crud for businedd catagery
            Route::apiResource('business-categories', BusinessCategoryController::class);
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


        Route::post('/check-package-allowance', [PermissionController::class, 'packagesAllowCheck']);




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

        Route::post('change-emp-status/{id}', [EmployeeController::class, 'changeEmpStatus']);


        // Companies API's
        Route::prefix('companies')->group(function () {
            Route::get('/', [CompanyController::class, 'index'])->middleware('permission:view company');
            Route::post('/', [CompanyController::class, 'store'])->middleware('permission:add company');
            Route::get('{id}', [CompanyController::class, 'show'])->middleware('permission:view company');
            Route::put('{id}', [CompanyController::class, 'update'])->middleware('permission:update company');
            Route::delete('{id}', [CompanyController::class, 'destroy'])->middleware('permission:delete company');
        });


        Route::prefix('company')->group(function () {
            Route::post('account', [CompanyController::class, 'addAccountsInCompany']);
            Route::get('accounts', [CompanyController::class, 'getCompanyAccounts']);
            Route::get('account/{id}', [CompanyController::class, 'getSingleCompanyAccount']);
            Route::put('account/{id}', [CompanyController::class, 'updateCompanyAccount']);
            Route::delete('account/{id}', [CompanyController::class, 'deleteCompanyAccount']);




            // LEAVES
            Route::get('leaves', [LeavesAndHolidayController::class, 'getLeaves']);
            Route::post('leaves', [LeavesAndHolidayController::class, 'createLeave']);
            Route::put('leaves/{id}', [LeavesAndHolidayController::class, 'updateLeave']);
            Route::delete('leaves/{id}', [LeavesAndHolidayController::class, 'deleteLeave']);

            // HOLIDAYS
            Route::get('holidays', [LeavesAndHolidayController::class, 'getHolidays']);
            Route::post('holidays', [LeavesAndHolidayController::class, 'createHoliday']);
            Route::put('holidays/{id}', [LeavesAndHolidayController::class, 'updateHoliday']);
            Route::delete('holidays/{id}', [LeavesAndHolidayController::class, 'deleteHoliday']);
        });




        // Selected Companies API's


        Route::get('selectedCompanies', [CompanyController::class, 'getSelectedCompanies']);
        Route::post('selectedCompanies/{id}', [CompanyController::class, 'selectedCompanies']);


        // Attendance API's
        Route::prefix('attendance')->group(function () {
            Route::get('/summary', [AttendanceController::class, 'getAttendanceSummary']);
            Route::get('/balance-leave', [AttendanceController::class, 'getLeaveBalance']);
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
            Route::get('/{id}', [ShiftsController::class, 'show']);
            Route::match(['put', 'patch'], '/{id}', [ShiftsController::class, 'update']);
            Route::delete('{id}', [ShiftsController::class, 'destroy']);
        });


        // Store API's
        Route::prefix('store')->group(function () {
            // Items API's
            Route::post('add-items', [ItemsController::class, 'store']);
            Route::get('items', [ItemsController::class, 'index']);
            Route::get('items/{id}', [ItemsController::class, 'show']);
            Route::put('items/{id}', [ItemsController::class, 'update']);
            Route::delete('items/{id}', [ItemsController::class, 'destroy']);

            Route::prefix('item')->group(function () {
                Route::get('batch', [ItemStockController::class, 'index']);
                Route::get('batch/{id}', [ItemStockController::class, 'show']);
                Route::post('batch', [ItemStockController::class, 'store']);
                Route::put('batch/{id}', [ItemStockController::class, 'update']);
                Route::delete('batch/{id}', [ItemStockController::class, 'destroy']);
            });


            Route::get('measuring-units', [MeasuringUnitController::class, 'index']);
            Route::post('measuring-units', [MeasuringUnitController::class, 'store']);
            Route::get('measuring-units/{id}', [MeasuringUnitController::class, 'show']);
            Route::put('measuring-units/{id}', [MeasuringUnitController::class, 'update']);
            Route::delete('measuring-units/{id}', [MeasuringUnitController::class, 'destroy']);


            Route::post('bulk-items', [BulkActionsController::class, 'storeBulkItems']);
            Route::get('cat-items', [BulkActionsController::class, 'getItemCatTree']);
            Route::post('items/bulk-delete', [BulkActionsController::class, 'bulkDeleteItems']);

            // Vendors API's
            Route::get('vendors', [StoreVendorController::class, 'index']);
            Route::post('vendors', [StoreVendorController::class, 'store']);
            Route::get('vendors/{id}', [StoreVendorController::class, 'show']);
            Route::get('vendors/credit/{id}', [StoreVendorController::class, 'vendorCredit']);
            Route::put('vendors/{id}', [StoreVendorController::class, 'update']);
            Route::delete('vendors/{id}', [StoreVendorController::class, 'destroy']);


            Route::prefix('brands')->group(function () {
                Route::get('/', [StoreItemBrandController::class, 'index']);
                Route::post('/', [StoreItemBrandController::class, 'store']);
                Route::get('/{id}', [StoreItemBrandController::class, 'show']);
                Route::put('/{id}', [StoreItemBrandController::class, 'update']);
                // Route::patch('/{id}', [StoreItemBrandController::class, 'update']);
                Route::delete('/{id}', [StoreItemBrandController::class, 'destroy']);
            });
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

            // All payment history API's
            Route::prefix('/payments-history')->group(function () {
                Route::get('/online', [InvoicesController::class, 'onlinePaymentHistory']);
                Route::get('/cash', [InvoicesController::class, 'cashPaymentHistory']);
                Route::get('/card', [InvoicesController::class, 'cardPaymentHistory']);
                Route::get('/credit', [InvoicesController::class, 'creditPaymentHistory']);
                Route::get('/self-consumption', [InvoicesController::class, 'selfConsumptionHistory']);
            });


            // Invoice API's Basic
            Route::get('/', [InvoicesController::class, 'index']);
            Route::get('/{id}', [InvoicesController::class, 'show']);
            Route::get('/{id}/download', [InvoicesController::class, 'download']);
            Route::post('/', [InvoicesController::class, 'store']);
            Route::post('/print', [InvoicesController::class, 'storeAndPrint']);
            Route::post('/mail', [InvoicesController::class, 'storeAndMail']);
            Route::post('/{id}/store-whatsapp', [InvoicesController::class, 'sendToWhatsapp']);
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
            Route::get('/{id}', [ItemTaxController::class, 'show']);
            Route::put('/{id}', [ItemTaxController::class, 'update']);
            Route::delete('/{id}', [ItemTaxController::class, 'destroy']);
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
            Route::get('/{id}', [QuotationController::class, 'show']);
            Route::post('/', [QuotationController::class, 'store']);
            Route::get('/{id}/pdf', [QuotationController::class, 'generatePdf']);
        });


        // Add new company API's
        Route::prefix('add-new-company')->group(function () {
            Route::post('/pay', [AddNewCompanyController::class, 'paymentInitiate']);
            Route::post('/{id}', [AddNewCompanyController::class, 'store']);
        });

        Route::get('company-details', [CompanyController::class, 'companyDetails']);

        // Pricing and Packages API's
        Route::prefix('pricing-packages')->group(function () {
            Route::get('/', [PackageController::class, 'index']);
            Route::post('/', [PackageController::class, 'store']);
            Route::get('/{id}', [PackageController::class, 'show']);
            Route::put('/{id}', [PackageController::class, 'update']);
            Route::patch('/{id}', [PackageController::class, 'update']);
            Route::delete('/{id}', [PackageController::class, 'destroy']);
        });



        // Export and import Items
        Route::get('export-inline', [BulkActionsController::class, 'exportInline']);
        Route::post('import-inline', [BulkActionsController::class, 'importInline']);


        Route::post('/table/store', [TableColumnController::class, 'store']);
        Route::post('/table-listed/store', [TableColumnController::class, 'storeTable']);
        Route::post('/table-column/store', [TableColumnController::class, 'storeColumn']);



        // Payments and refund API's
        Route::prefix('payments')->group(function () {
            Route::get('/', [PaymentAndBillingController::class, 'adminBilling']);
            Route::post('/refund-request/{transaction_id}', [PaymentAndBillingController::class, 'refundRequest']);
            Route::post('/upgrade-package', [PaymentAndBillingController::class, 'upgradePackage']);
        });




        Route::prefix('expenses')->group(function () {
            Route::get('/', [ExpenseController::class, 'index'])->name('expenses.index');         // GET /api/expenses
            Route::post('/store', [ExpenseController::class, 'store'])->name('expenses.store');   // POST /api/expenses/store
            Route::get('/{expense}', [ExpenseController::class, 'show'])->name('expenses.show');  // GET /api/expenses/{id}
            Route::post('/{expense}/update', [ExpenseController::class, 'update'])->name('expenses.update'); // POST /api/expenses/{id}/update
            Route::delete('/{expense}/delete', [ExpenseController::class, 'destroy'])->name('expenses.destroy'); // DELETE /api/expenses/{id}/delete
        });
    });
});
