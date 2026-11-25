<?php

namespace App\Providers;

use App\Models\ClosedTrainingCase;
use App\Models\Instructor;
use App\Models\TrainerPortalNotification;
use App\Support\DestructiveDatabaseGuard;
use App\Support\TrainerImpersonation;
use App\View\Composers\DashboardResourceCountsComposer;
use App\View\Composers\MarketingAnalyticsSkipComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
        DestructiveDatabaseGuard::register();

        // Linki w mailach / route() muszą używać APP_URL (np. http://localhost:8081),
        // a nie Host z wewnętrznego wywołania ADM → http://pnedu-app/...
        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl !== '') {
            URL::forceRootUrl($appUrl);
        }

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

        View::composer(['layouts.app', 'layouts.trainer', 'layouts.navigation'], function ($view): void {
            $view->with([
                'trainerPreviewInstructor' => TrainerImpersonation::previewInstructor(),
                'trainerSwitchInstructors' => TrainerImpersonation::switchList(),
            ]);
        });

        View::composer('layouts.navigation', function ($view): void {
            $enabled = false;
            $caseCount = 0;
            $unread = 0;
            $userId = auth()->id();
            if ($userId) {
                try {
                    $instructorId = Instructor::query()
                        ->where('pnedu_user_id', $userId)
                        ->where('portal_access_enabled', true)
                        ->value('id');
                    $enabled = $instructorId !== null;
                    if ($enabled) {
                        $caseCount = ClosedTrainingCase::query()
                            ->where('instructor_id', $instructorId)
                            ->where('show_in_trainer_panel', true)
                            ->count();
                        $unread = TrainerPortalNotification::query()
                            ->where('audience', TrainerPortalNotification::AUDIENCE_TRAINER)
                            ->where('pnedu_user_id', $userId)
                            ->whereNull('read_at')
                            ->count();
                    }
                } catch (\Throwable) {
                    $enabled = false;
                    $caseCount = 0;
                    $unread = 0;
                }
            }
            $view->with('trainerPortalEnabled', $enabled);
            $view->with('trainerPortalCaseCount', $caseCount);
            $view->with('trainerUnreadCount', $unread);
        });

        View::composer('layouts.trainer', function ($view): void {
            $unread = 0;
            $userId = TrainerImpersonation::previewInstructor()?->pnedu_user_id ?: auth()->id();
            if ($userId) {
                try {
                    $unread = TrainerPortalNotification::query()
                        ->where('audience', TrainerPortalNotification::AUDIENCE_TRAINER)
                        ->where('pnedu_user_id', $userId)
                        ->whereNull('read_at')
                        ->count();
                } catch (\Throwable) {
                    $unread = 0;
                }
            }
            $view->with('trainerUnreadCount', $unread);
        });
    }
}
