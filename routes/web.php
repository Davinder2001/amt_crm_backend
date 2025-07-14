<?php

use Illuminate\Support\Facades\Route;

// Health check route for deployment
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0',
        'environment' => config('app.env'),
        'message' => 'AMT CRM Backend is running'
    ]);
});

Route::get('/', function () {
    return view('welcome');
});
