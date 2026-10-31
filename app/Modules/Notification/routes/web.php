<?php

use App\Modules\Notification\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/preferences', [NotificationController::class, 'preferences'])->name('notifications.preferences');
    Route::get('/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');
});
