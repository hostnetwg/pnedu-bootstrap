<?php

namespace App\Providers;

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
        // ServiceProvider dla pne-certificate-generator jest automatycznie wykrywany
        // przez Laravel dzięki konfiguracji w composer.json pakietu
    }
}
