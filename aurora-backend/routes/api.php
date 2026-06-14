<?php

use App\Http\Controllers\Api\BrandProfileController;
use App\Http\Controllers\Api\DraftController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\TelegramChannelController;
use App\Http\Controllers\Webhook\TelegramWebhookController;
use App\Http\Middleware\RequireDashboardToken;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', [HealthController::class, 'show']);

    Route::middleware(RequireDashboardToken::class)->group(function () {
        Route::get('/drafts', [DraftController::class, 'index']);
        Route::post('/drafts', [DraftController::class, 'store']);
        Route::get('/drafts/{draft}', [DraftController::class, 'show']);
        Route::post('/drafts/{draft}/select-variant', [DraftController::class, 'selectVariant']);
        Route::post('/drafts/{draft}/approve', [DraftController::class, 'approve']);
        Route::post('/drafts/{draft}/publish', [DraftController::class, 'publish']);
        Route::post('/drafts/{draft}/cancel', [DraftController::class, 'cancel']);

        Route::get('/telegram/channel', [TelegramChannelController::class, 'show']);
        Route::post('/telegram/channel/connect', [TelegramChannelController::class, 'connect']);
        Route::delete('/telegram/channel', [TelegramChannelController::class, 'disconnect']);

        Route::get('/brand-profile', [BrandProfileController::class, 'show']);
        Route::put('/brand-profile', [BrandProfileController::class, 'update']);
    });
});

Route::post('/webhook/telegram', [TelegramWebhookController::class, 'handle']);
