<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CaseController;

Route::get('/', function () {
    return view('welcome');
});

// API Routes (Prefixed with /api temporarily without installing install:api)
Route::prefix('api')->group(function () {
    Route::post('/cases', [CaseController::class, 'store']);
    Route::post('/cases/{id}/transition', [CaseController::class, 'transition']);
    Route::get('/cases/{id}/events', [CaseController::class, 'events']);
    Route::post('/cases/{id}/documents/45-days-notice', [CaseController::class, 'generateNoticeDocument']);
    Route::post('/cases/{id}/documents/dishonour-certificate', [CaseController::class, 'generateDishonourDocument']);
    Route::post('/cases/{id}/documents/cover-note', [CaseController::class, 'generateCoverNoteDocument']);
    
    // User Management
    Route::get('/users', [\App\Http\Controllers\UserController::class, 'index']);
    Route::post('/users', [\App\Http\Controllers\UserController::class, 'store']);
    Route::put('/users/{id}', [\App\Http\Controllers\UserController::class, 'update']);
    Route::delete('/users/{id}', [\App\Http\Controllers\UserController::class, 'destroy']);
});
