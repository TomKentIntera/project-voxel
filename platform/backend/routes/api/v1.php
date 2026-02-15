<?php

use App\Http\Controllers\Api\V1\LegacyDomainController;
use App\Http\Controllers\Api\V1\SystemHealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [SystemHealthController::class, 'show'])->name('health.show');
Route::get('/migration/legacy-domains', [LegacyDomainController::class, 'index'])->name('migration.legacy-domains.index');
