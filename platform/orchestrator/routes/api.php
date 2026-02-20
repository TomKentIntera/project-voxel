<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\RegionalProxyController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AdminAuthController::class, 'register']);
    Route::post('/login', [AdminAuthController::class, 'login']);
    Route::middleware(['auth.jwt', 'admin'])->group(function (): void {
        Route::post('/refresh', [AdminAuthController::class, 'refresh']);
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::get('/me', [AdminAuthController::class, 'me']);
    });
});

Route::middleware(['regional-proxy.auth'])->group(function (): void {
    Route::get('/internal/proxy-bindings', [RegionalProxyController::class, 'bindings']);
    Route::get('/regional-proxies/mappings', [RegionalProxyController::class, 'mappings']);
    Route::get('/regional-proxies/{id}/mappings', [RegionalProxyController::class, 'mappingsById'])
        ->whereNumber('id');
});

Route::middleware(['auth.jwt', 'admin'])->group(function (): void {
    Route::get('/servers', [ServerController::class, 'index']);
    Route::get('/servers/{id}', [ServerController::class, 'show']);
    Route::get('/regional-proxies', [RegionalProxyController::class, 'index']);
    Route::get('/regional-proxies/{id}', [RegionalProxyController::class, 'show']);
    Route::post('/regional-proxies', [RegionalProxyController::class, 'store']);
    Route::get('/metrics', [MetricsController::class, 'index']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
});

Route::get('/banner', [BannerController::class, 'index']);
Route::get('/plans', [PlanController::class, 'index']);
Route::get('/plans/recommend', [PlanController::class, 'recommend']);
Route::get('/faqs', [FaqController::class, 'index']);
