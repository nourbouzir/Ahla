<?php

use App\Modules\Payment\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/payments/webhook', [WebhookController::class, 'handle'])
    ->middleware('throttle:120,1')
    ->name('payments.webhook');
