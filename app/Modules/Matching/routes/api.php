<?php

use App\Modules\Matching\Http\Controllers\DiscoverController;
use Illuminate\Support\Facades\Route;

Route::get('/experiences', [DiscoverController::class, 'index'])->name('experiences.index');
