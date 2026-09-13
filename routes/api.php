<?php

use App\Http\Controllers\Api\AttendanceApiController;
use Illuminate\Support\Facades\Route;

// Add these lines into your existing routes/api.php

Route::middleware('verify.api.key')->prefix('attendance')->group(function () {
    Route::get('/devices', [AttendanceApiController::class, 'devices']);
    Route::get('/logs', [AttendanceApiController::class, 'logs']);
    Route::post('/sync/{device?}', [AttendanceApiController::class, 'syncNow']);
});

/*
Register the middleware alias in app/Http/Kernel.php under $middlewareAliases (or $routeMiddleware on older Laravel):

    'verify.api.key' => \App\Http\Middleware\VerifyApiKey::class,
*/
