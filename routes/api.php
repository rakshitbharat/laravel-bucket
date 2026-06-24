<?php

use Illuminate\Support\Facades\Route;
use LaraBucket\Http\Controllers\AuthController;
use LaraBucket\Http\Controllers\BucketController;
use LaraBucket\Http\Controllers\StorageController;
use LaraBucket\Http\Controllers\AdminController;
use LaraBucket\Http\Middleware\AuthenticateLaraBucketAdmin;
use LaraBucket\Http\Middleware\AuthenticateLaraBucketClient;

$prefix = config('larabucket.server.route_prefix', 'api');
$middleware = config('larabucket.server.middleware', ['api']);

Route::prefix($prefix)->middleware($middleware)->group(function () {
    // Public/Admin authentication
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Admin Authenticated Dashboard routes
    Route::middleware(AuthenticateLaraBucketAdmin::class)->group(function () {
        Route::get('/buckets', [BucketController::class, 'index']);
        Route::post('/buckets', [BucketController::class, 'store']);
        Route::put('/buckets/{id}', [BucketController::class, 'update']);
        Route::delete('/buckets/{id}', [BucketController::class, 'destroy']);
        
        Route::get('/buckets/{bucketId}/files', [BucketController::class, 'files']);
        Route::post('/buckets/{bucketId}/folders', [BucketController::class, 'makeFolder']);
        Route::delete('/files/{fileId}', [BucketController::class, 'deleteFile']);
    });

    // Client/Adapter Storage routes
    Route::middleware(AuthenticateLaraBucketClient::class)->group(function () {
        Route::match(['GET', 'HEAD'], '/files', [StorageController::class, 'exists']);
        Route::post('/buckets/{bucket}/upload', [StorageController::class, 'upload']);
        Route::get('/files/download', [StorageController::class, 'download']);
        Route::delete('/files', [StorageController::class, 'delete']);
        Route::get('/files/metadata', [StorageController::class, 'metadata']);
        Route::post('/files/copy', [StorageController::class, 'copy']);
    });
});

// Admin Panel Web View Route
Route::middleware(['web'])->group(function () {
    Route::get('/admin/larabucket', [AdminController::class, 'dashboard']);
    Route::get('/storage/{path}', [StorageController::class, 'servePublicFile'])->where('path', '.*');
});

