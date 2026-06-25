<?php

namespace App\Providers;

use App\View\Composers\DashboardResourceCountsComposer;
use App\View\Composers\MarketingAnalyticsSkipComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        // Etap B1 — rate limit dla publicznego endpointu JS analityki (per IP, fail-silent).
        RateLimiter::for('analytics-client-events', function (Request $request) {
            $perMinute = max(1, (int) config('analytics.client_events.rate_limit_per_minute', 60));

            return Limit::perMinute($perMinute)->by($request->ip());
        });

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
