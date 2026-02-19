<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AdminAuthController::class, 'register']);
    Route::post('/login', [AdminAuthController::class, 'login']);
    Route::post('/refresh', [AdminAuthController::class, 'refresh']);
    Route::post('/logout', [AdminAuthController::class, 'logout']);
    Route::middleware('auth.jwt')->get('/me', [AdminAuthController::class, 'me']);
});

Route::middleware(['auth.jwt', 'admin'])->group(function (): void {
    Route::get('/servers', [ServerController::class, 'index']);
    Route::get('/metrics', [MetricsController::class, 'index']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
});

Route::get('/banner', [BannerController::class, 'index']);
Route::get('/plans', [PlanController::class, 'index']);
Route::get('/plans/recommend', [PlanController::class, 'recommend']);
Route::get('/faqs', [FaqController::class, 'index']);
