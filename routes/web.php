<?php

use App\Http\Controllers\BuildingController;
use App\Http\Controllers\BusinessRateBillController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\OccupancyReportController;
use App\Http\Controllers\TenancyController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

Route::get('/', OccupancyReportController::class)->name('home');

Route::resource('companies', CompanyController::class)->except('show');
Route::resource('buildings', BuildingController::class)->except('show');
Route::resource('units', UnitController::class)->except('show');
Route::resource('tenants', TenantController::class)->except('show');
Route::resource('tenancies', TenancyController::class)->except('show');
Route::resource('business-rate-bills', BusinessRateBillController::class)->except('show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
