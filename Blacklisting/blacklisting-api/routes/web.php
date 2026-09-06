<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CibEntityController;
use App\Http\Controllers\ScreeningController;
use App\Http\Controllers\CibImportController;

Route::get('/', function () {
    return view('welcome');
});

// API Routes (Prefixed with /api temporarily without installing install:api)
Route::prefix('api')->group(function () {
    // Auth Routes
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // CIB Routes
    Route::post('/cib/upload', [CibEntityController::class, 'upload']);
    Route::get('/cib/search', [CibEntityController::class, 'search']);
    Route::get('/cib/stats', [CibEntityController::class, 'stats']);
    
    // Screening & CIB Blacklist (New)
    Route::post('/screening/screen', [ScreeningController::class, 'screen']);
    Route::get('/screening/logs', [ScreeningController::class, 'logs']);
    Route::post('/cib-blacklists/import', [CibImportController::class, 'import']);
    Route::get('/cib-blacklists/latest-upload', [CibImportController::class, 'latestUploadInfo']);

    Route::get('/applications', [\App\Http\Controllers\ApplicationController::class, 'index']);
    Route::post('/applications', [\App\Http\Controllers\ApplicationController::class, 'store']);
    Route::get('/applications/{id}', [\App\Http\Controllers\ApplicationController::class, 'show']);
    Route::patch('/applications/{id}/profile', [\App\Http\Controllers\ApplicationController::class, 'updateProfile']);
    Route::post('/applications/{id}/targets', [\App\Http\Controllers\ApplicationController::class, 'addTarget']);

    Route::post('/cases/lodge', [CaseController::class, 'lodge']); // Keep for legacy/fallback
    Route::post('/cases/{id}/transition', [CaseController::class, 'transition']);
    Route::get('/cases/{id}/events', [CaseController::class, 'events']);
    Route::post('/cases/{id}/documents/45-days-notice', [CaseController::class, 'generateNoticeDocument']);
    Route::post('/cases/{id}/documents/dishonour-certificate', [CaseController::class, 'generateDishonourDocument']);
    Route::post('/cases/{id}/documents/cover-note', [CaseController::class, 'generateCoverNoteDocument']);
    
    // Timeline endpoints
    Route::post('/cases/{id}/verify', [CaseController::class, 'verifyProfile']);
    Route::post('/cases/{id}/upload-notice-proofs', [CaseController::class, 'uploadNoticeProofs']);
    Route::post('/cases/{id}/issue-dishonour', [CaseController::class, 'issueDishonourCertificate']);
    Route::post('/cases/{id}/confirm-blacklisting', [CaseController::class, 'confirmBlacklisting']);
    
    // Master Data Routes (SOL Branches & Nepal Locations)
    Route::get('/sol-branches', function () {
        return response()->json(['success' => true, 'data' => \Illuminate\Support\Facades\DB::table('sol_branches')->where('is_active', 1)->get()]);
    });
    Route::get('/nepal-locations', function () {
        return response()->json(['success' => true, 'data' => \Illuminate\Support\Facades\DB::table('nepal_locations')->get()]);
    });

    // User Management
    Route::get('/users', [\App\Http\Controllers\UserController::class, 'index']);
    Route::post('/users', [\App\Http\Controllers\UserController::class, 'store']);
    Route::put('/users/{id}', [\App\Http\Controllers\UserController::class, 'update']);
    Route::delete('/users/{id}', [\App\Http\Controllers\UserController::class, 'destroy']);
});
