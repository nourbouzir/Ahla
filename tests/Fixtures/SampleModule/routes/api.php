<?php

use Illuminate\Support\Facades\Route;

Route::get('/sample-module', fn () => response()->json(['module' => 'sample']));
