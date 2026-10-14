<?php

use App\Http\Controllers\BillerController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\BusinessRateBillController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\OccupancyReportController;
use App\Http\Controllers\TenancyController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

Route::get('/', OccupancyReportController::class)->name('home');

Route::resource('billers', BillerController::class)->only(['index', 'store', 'update', 'destroy']);
Route::resource('companies', CompanyController::class)->only(['index', 'store', 'update', 'destroy']);
Route::resource('buildings', BuildingController::class)->only(['index', 'store', 'update', 'destroy']);
Route::resource('units', UnitController::class)->only(['index', 'store', 'update', 'destroy']);
Route::resource('tenants', TenantController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
Route::resource('tenancies', TenancyController::class)->except('show');
Route::resource('business-rate-bills', BusinessRateBillController::class);
Route::post('business-rate-bills/{businessRateBill}/documents', [BusinessRateBillController::class, 'storeDocuments'])->name('business-rate-bills.documents.store');
Route::resource('documents', DocumentController::class)->only(['index', 'store', 'show', 'destroy']);
Route::resource('imports', ImportController::class)->only(['index', 'store']);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
