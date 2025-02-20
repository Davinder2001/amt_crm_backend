<?php

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdminCreateController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\EmpRoleController;



// Admin, Employee, and Role CRUD Routes
Route::apiResource('/v1/admins', AdminCreateController::class);
Route::apiResource('/v1/employees', EmployeeController::class);
Route::apiResource('/v1/employeroles', EmpRoleController::class);



// Define login route explicitly
Route::post('/v1/login', [LoginController::class, 'login'])->name('login');
// Route::post('/v1/logout', [LoginController::class, 'logout'])->middleware('auth:sanctum');


Route::apiResource('/v1/users', UserController::class);

Route::middleware(['auth:api', 'role:admin'])->group(function () {
    Route::get('/v1/users', [UserController::class, 'index'])->name('get-user');
});
