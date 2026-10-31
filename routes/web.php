<?php

use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::post('/locale/{locale}', [LocaleController::class, 'update'])->name('locale.update');
