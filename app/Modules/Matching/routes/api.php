<?php

use App\Modules\Matching\Http\Controllers\SearchApiController;
use Illuminate\Support\Facades\Route;

Route::get('/search', SearchApiController::class)->middleware('throttle:search')->name('api.search');
