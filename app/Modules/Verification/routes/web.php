<?php

use App\Modules\Verification\Http\Controllers\ApplicationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:host.apply'])->prefix('host/verification')->group(function () {
    Route::get('/', [ApplicationController::class, 'show'])->name('host.verification.show');
    Route::post('/', [ApplicationController::class, 'store'])->middleware('throttle:6,1')->name('host.verification.store');
});
