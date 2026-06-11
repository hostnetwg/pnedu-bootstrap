<?php

namespace App\Providers;

use App\View\Composers\DashboardResourceCountsComposer;
use Illuminate\Support\Facades\View;
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
        View::composer([
            'layouts.navigation',
            'dashboard.partials.sidebar-nav',
            'dashboard.partials.sidebar-nav-menu',
            'dashboard.index',
        ], DashboardResourceCountsComposer::class);
    }
}
