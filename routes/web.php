<?php

use App\Http\Controllers\AttendanceDashboardController;
use App\Http\Controllers\ApiClientController;
use Illuminate\Support\Facades\Route;

// Add these lines into your existing routes/web.php
// (wrap in your normal auth/admin middleware group as needed)

Route::prefix('attendance')->name('attendance.')->group(function () {
    Route::get('/', [AttendanceDashboardController::class, 'index'])->name('dashboard');
    Route::post('/devices', [AttendanceDashboardController::class, 'storeDevice'])->name('devices.store');
    Route::post('/sync/{device?}', [AttendanceDashboardController::class, 'syncNow'])->name('sync');
    Route::get('/logs', [AttendanceDashboardController::class, 'logs'])->name('logs');
    Route::get('/sync-history', [AttendanceDashboardController::class, 'syncHistory'])->name('sync-history');

    // API client (key) management
    Route::get('/api-clients', [ApiClientController::class, 'index'])->name('api-clients.index');
    Route::post('/api-clients', [ApiClientController::class, 'store'])->name('api-clients.store');
    Route::post('/api-clients/{client}/revoke', [ApiClientController::class, 'revoke'])->name('api-clients.revoke');
});
