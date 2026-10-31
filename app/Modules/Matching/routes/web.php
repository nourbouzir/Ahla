<?php

use App\Modules\Matching\Http\Controllers\DiscoverController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DiscoverController::class, 'index'])->name('discover');
