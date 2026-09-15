<?php

use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\CampusApiController;
use App\Http\Controllers\Api\V1\DashboardApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:60,1')->group(function () {
    Route::post('/auth/token', [AuthTokenController::class, 'store']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/dashboard', DashboardApiController::class);
        Route::get('/students', [CampusApiController::class, 'students']);
        Route::get('/lecturers', [CampusApiController::class, 'lecturers']);
        Route::get('/courses', [CampusApiController::class, 'courses']);
        Route::get('/krs', [CampusApiController::class, 'krs']);
        Route::get('/invoices', [CampusApiController::class, 'invoices']);
        Route::get('/payments', [CampusApiController::class, 'payments']);
        Route::get('/scholarships', [CampusApiController::class, 'scholarships']);
        Route::get('/thesis', [CampusApiController::class, 'thesis']);
        Route::get('/graduations', [CampusApiController::class, 'graduations']);
        Route::get('/library-books', [CampusApiController::class, 'libraryBooks']);
        Route::get('/research', [CampusApiController::class, 'research']);
        Route::get('/mbkm', [CampusApiController::class, 'mbkm']);
    });
});
