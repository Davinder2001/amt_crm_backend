<?php

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\EmpRoleController;




Route::apiResource('/v1/employees', EmployeeController::class);
Route::apiResource('/v1/employeroles', EmpRoleController::class);


//  Admin CRUD Route
Route::apiResource('/v1/admins', AdminAuthController::class);







// Authentication Routes
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware(['admin.auth'])->group(function () {
    Route::apiResource('/users', UserController::class);
});


// Route for authenticated user
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
