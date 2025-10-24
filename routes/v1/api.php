<?php

use App\Http\Controllers\Telegram\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/telegram/webhook/{token}', WebhookController::class)
    ->where('token', config('telegram.secret'))
    ->middleware('telegram');;

