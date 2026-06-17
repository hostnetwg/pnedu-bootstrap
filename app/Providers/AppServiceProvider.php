<?php

namespace App\Providers;

use App\View\Composers\DashboardResourceCountsComposer;
use App\View\Composers\MarketingAnalyticsSkipComposer;
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

        View::composer([
            'layouts.analytics-head',
            'layouts.google-tag-manager-body',
            'layouts.cookie-consent',
            'courses.partials.marketing-ga-event',
        ], MarketingAnalyticsSkipComposer::class);
    }
}
