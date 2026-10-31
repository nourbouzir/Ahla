<?php

use App\Modules\Admin\Http\Controllers\AuditController;
use App\Modules\Admin\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:admin.verifications'])->prefix('admin')->group(function () {
    Route::get('/verifications', [VerificationController::class, 'index'])->name('admin.verifications.index');
    Route::get('/verifications/{verification}', [VerificationController::class, 'show'])->name('admin.verifications.show');
    Route::get('/verifications/{verification}/evidence', [VerificationController::class, 'evidence'])->name('admin.verifications.evidence');
    Route::post('/verifications/{verification}/schedule', [VerificationController::class, 'schedule'])->name('admin.verifications.schedule');
    Route::post('/verifications/{verification}/decide', [VerificationController::class, 'decide'])->name('admin.verifications.decide');
    Route::post('/hosts/{host}/status', [VerificationController::class, 'setHostStatus'])->middleware('can:admin.host-status')->name('admin.hosts.status');
    Route::get('/audit', [AuditController::class, 'index'])->middleware('can:admin.audit')->name('admin.audit');
});
