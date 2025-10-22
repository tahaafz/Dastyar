<?php

use App\Http\Controllers\Telegram\WebhookController;

Route::post('/telegram/webhook/{token}', WebhookController::class)
    ->where('token', config('telegram.secret'))
    ->middleware('telegram');;
