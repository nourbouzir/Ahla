<?php

use App\Modules\Booking\Http\Controllers\BookingController;
use App\Modules\Booking\Http\Controllers\HostBookingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::middleware('can:booking.request')->group(function () {
        Route::get('/experiences/{experience}/book', [BookingController::class, 'create'])->name('bookings.create');
        Route::post('/experiences/{experience}/book', [BookingController::class, 'store'])->middleware('throttle:20,1')->name('bookings.store');
        Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
    });

    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->middleware('can:booking.cancel')->name('bookings.cancel');

    Route::middleware('can:booking.respond')->prefix('host/bookings')->group(function () {
        Route::get('/', [HostBookingController::class, 'index'])->name('host.bookings.index');
        Route::post('/{booking}/accept', [HostBookingController::class, 'accept'])->name('host.bookings.accept');
        Route::post('/{booking}/decline', [HostBookingController::class, 'decline'])->name('host.bookings.decline');
    });
});
