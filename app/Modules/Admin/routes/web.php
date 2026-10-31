<?php

use App\Modules\Admin\Http\Controllers\AuditController;
use App\Modules\Admin\Http\Controllers\BookingOverviewController;
use App\Modules\Admin\Http\Controllers\DashboardController;
use App\Modules\Admin\Http\Controllers\GuideController;
use App\Modules\Admin\Http\Controllers\RevenueController;
use App\Modules\Admin\Http\Controllers\UserController;
use App\Modules\Admin\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:admin.verifications'])->prefix('admin')->group(function () {
    Route::get('/', DashboardController::class)->name('admin.dashboard');

    Route::get('/verifications', [VerificationController::class, 'index'])->name('admin.verifications.index');
    Route::get('/verifications/{verification}', [VerificationController::class, 'show'])->name('admin.verifications.show');
    Route::get('/verifications/{verification}/evidence', [VerificationController::class, 'evidence'])->name('admin.verifications.evidence');
    Route::post('/verifications/{verification}/schedule', [VerificationController::class, 'schedule'])->name('admin.verifications.schedule');
    Route::post('/verifications/{verification}/decide', [VerificationController::class, 'decide'])->name('admin.verifications.decide');

    Route::get('/guides', GuideController::class)->middleware('can:admin.guides')->name('admin.hosts.index');
    Route::post('/hosts/{host}/status', [VerificationController::class, 'setHostStatus'])->middleware('can:admin.host-status')->name('admin.hosts.status');

    Route::middleware('can:admin.users')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('admin.users.show');
        Route::post('/users/{user}/disable', [UserController::class, 'disable'])->name('admin.users.disable');
        Route::post('/users/{user}/enable', [UserController::class, 'enable'])->name('admin.users.enable');
    });

    Route::get('/bookings', BookingOverviewController::class)->middleware('can:admin.bookings')->name('admin.bookings.index');
    Route::get('/revenue', [RevenueController::class, 'index'])->middleware('can:admin.revenue')->name('admin.revenue');
    Route::get('/revenue.csv', [RevenueController::class, 'csv'])->middleware('can:admin.revenue')->name('admin.revenue.csv');
    Route::get('/audit', [AuditController::class, 'index'])->middleware('can:admin.audit')->name('admin.audit');
});
