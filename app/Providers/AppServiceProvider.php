<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Relative Zeitangaben ("vor 5 Minuten") deutsch, unabhängig von APP_LOCALE.
        Carbon::setLocale('de');
    }
}
