<?php

use App\Http\Controllers\Webhook\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/webhook/telegram', [TelegramWebhookController::class, 'handle']);
