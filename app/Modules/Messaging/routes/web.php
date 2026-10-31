<?php

use App\Modules\Messaging\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:message.send'])->prefix('bookings/{booking}/messages')->group(function () {
    Route::get('/', [MessageController::class, 'show'])->name('messages.show');
    Route::get('/poll', [MessageController::class, 'index'])->middleware('throttle:120,1')->name('messages.poll');
    Route::post('/', [MessageController::class, 'store'])->middleware('throttle:30,1')->name('messages.store');
});
