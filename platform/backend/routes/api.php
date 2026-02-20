<?php

use Interadigital\CoreAuth\Http\Controllers\AuthController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ServerController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::middleware('auth.jwt')->get('/me', [AuthController::class, 'me']);
});

Route::middleware('auth.jwt')->group(function (): void {
    Route::get('/servers', [ServerController::class, 'index']);
    Route::get('/servers/{uuid}/panel-url', [ServerController::class, 'panelUrl']);
});

Route::get('/banner', [BannerController::class, 'index']);
Route::get('/plans', [PlanController::class, 'index']);
Route::get('/plans/recommend', [PlanController::class, 'recommend']);
Route::get('/faqs', [FaqController::class, 'index']);
