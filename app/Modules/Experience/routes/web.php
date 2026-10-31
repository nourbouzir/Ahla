<?php

use App\Modules\Experience\Http\Controllers\AvailabilityController;
use App\Modules\Experience\Http\Controllers\ExperienceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:experience.manage'])->prefix('host')->group(function () {
    Route::get('/experiences', [ExperienceController::class, 'index'])->name('host.experiences.index');
    Route::get('/experiences/create', [ExperienceController::class, 'create'])->name('host.experiences.create');
    Route::post('/experiences', [ExperienceController::class, 'store'])->name('host.experiences.store');
    Route::get('/experiences/{experience}/edit', [ExperienceController::class, 'edit'])->name('host.experiences.edit');
    Route::put('/experiences/{experience}', [ExperienceController::class, 'update'])->name('host.experiences.update');
    Route::post('/experiences/{experience}/toggle', [ExperienceController::class, 'toggle'])->name('host.experiences.toggle');

    Route::get('/availability', [AvailabilityController::class, 'index'])->name('host.availability.index');
    Route::post('/availability/slots', [AvailabilityController::class, 'storeSlot'])->name('host.availability.slots.store');
    Route::delete('/availability/slots/{slot}', [AvailabilityController::class, 'destroySlot'])->name('host.availability.slots.destroy');
    Route::post('/availability/blocked', [AvailabilityController::class, 'storeBlocked'])->name('host.availability.blocked.store');
    Route::delete('/availability/blocked/{blocked}', [AvailabilityController::class, 'destroyBlocked'])->name('host.availability.blocked.destroy');
});
