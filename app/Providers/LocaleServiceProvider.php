<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class LocaleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        app()->setLocale(config('app.locale', 'fa'));
    }
}
