<?php

use App\Http\Controllers\Redirect\TelegramCollectLinkRedirectController;
use Illuminate\Support\Facades\Route;

Route::get('/s/{slug}', TelegramCollectLinkRedirectController::class)
    ->where('slug', '.*')
    ->name('telegram.collect_link.redirect');
