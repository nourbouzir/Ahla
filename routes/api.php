<?php

use Illuminate\Support\Facades\Route;

// App-wide JSON routes only (served under /api). Feature routes live in each
// module's own routes/api.php, see app/Modules/ModuleServiceProvider.php.

Route::get('/health', fn () => response()->json(['status' => 'ok']));
