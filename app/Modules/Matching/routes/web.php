<?php

use App\Modules\Matching\Http\Controllers\HostPageController;
use App\Modules\Matching\Http\Controllers\SearchPageController;
use Illuminate\Support\Facades\Route;

Route::get('/search', SearchPageController::class)->name('search');
Route::get('/hosts/{host}', [HostPageController::class, 'show'])->name('hosts.show');
