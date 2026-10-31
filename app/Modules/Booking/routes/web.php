<?php

use App\Modules\Booking\Http\Controllers\BookingController;
use App\Modules\Booking\Http\Controllers\Host\CalendarController;
use App\Modules\Booking\Http\Controllers\Host\ClientController;
use App\Modules\Booking\Http\Controllers\Host\DashboardController;
use App\Modules\Booking\Http\Controllers\Host\EarningsController;
use App\Modules\Booking\Http\Controllers\Host\InboxController;
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

    // The guide space: everything a host needs to run the day, behind the same ability as answering requests.
    Route::middleware('can:booking.respond')->prefix('host')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('host.dashboard');
        Route::get('/dashboard/summary', [DashboardController::class, 'summary'])->middleware('throttle:60,1')->name('host.dashboard.summary');
        Route::get('/calendar', CalendarController::class)->name('host.calendar');
        Route::get('/clients', [ClientController::class, 'index'])->name('host.clients.index');
        Route::get('/clients/{tourist}', [ClientController::class, 'show'])->name('host.clients.show');
        Route::put('/clients/{tourist}/note', [ClientController::class, 'saveNote'])->name('host.clients.note');
        Route::get('/inbox', InboxController::class)->name('host.inbox');
        Route::get('/earnings', [EarningsController::class, 'index'])->name('host.earnings');
        Route::get('/earnings.csv', [EarningsController::class, 'csv'])->name('host.earnings.csv');

        Route::prefix('bookings')->group(function () {
            Route::get('/', [HostBookingController::class, 'index'])->name('host.bookings.index');
            Route::post('/{booking}/accept', [HostBookingController::class, 'accept'])->name('host.bookings.accept');
            Route::post('/{booking}/decline', [HostBookingController::class, 'decline'])->name('host.bookings.decline');
        });
    });
});
