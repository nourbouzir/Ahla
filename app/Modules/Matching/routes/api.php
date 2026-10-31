<?php

use App\Modules\Matching\Http\Controllers\SearchApiController;
use Illuminate\Support\Facades\Route;

Route::get('/search', SearchApiController::class)->middleware('throttle:60,1')->name('api.search');
