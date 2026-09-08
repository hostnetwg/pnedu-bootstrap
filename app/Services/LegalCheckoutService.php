<?php

namespace App\Services;

use App\Models\Course;
use App\Support\OrderFormCustomerProfile;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

final class LegalCheckoutService
{
    public function requiresEarlyPerformanceStatement(
        Course $course,
        string $customerProfile,
        ?CarbonInterface $orderedAt = null
    ): bool {
        if (! $course->is_paid || ! $course->start_date) {
            return false;
        }

        if (! OrderFormCustomerProfile::isConsumerProtected($customerProfile)) {
            return false;
        }

        $timezone = config('app.timezone', 'Europe/Warsaw');
        $orderMoment = CarbonImmutable::instance($orderedAt ?? now())->setTimezone($timezone);
        $withdrawalDeadline = $orderMoment
            ->addDays((int) config('legal.early_performance.window_days', 14))
            ->endOfDay();
        $liveStart = CarbonImmutable::instance($course->start_date)->setTimezone($timezone);

        return $liveStart->lessThanOrEqualTo($withdrawalDeadline);
    }

    public function assertRequiredStatementAccepted(
        Course $course,
        string $customerProfile,
        mixed $accepted,
        ?CarbonInterface $orderedAt = null
    ): void {
        if (! $this->requiresEarlyPerformanceStatement($course, $customerProfile, $orderedAt)) {
            return;
        }

        if (! filter_var($accepted, FILTER_VALIDATE_BOOLEAN)) {
            throw ValidationException::withMessages([
                'early_performance_accepted' => 'Aby wziąć udział w szkoleniu rozpoczynającym się przed upływem 14 dni, zaznacz wymagane oświadczenie.',
            ]);
        }
    }

    /**
     * @return array{
     *     customer_profile: string,
     *     terms_version: string,
     *     terms_hash: string,
     *     early_performance_scope: string|null,
     *     early_performance_statement_version: string|null,
     *     early_performance_accepted_at: \Carbon\CarbonInterface|null
     * }
     */
    public function evidence(
        Course $course,
        string $customerProfile,
        bool $accepted,
        ?CarbonInterface $orderedAt = null
    ): array {
        $required = $this->requiresEarlyPerformanceStatement($course, $customerProfile, $orderedAt);
        $termsVersion = (string) config('legal.terms.current_version');

        return [
            'customer_profile' => $customerProfile,
            'terms_version' => $termsVersion,
            'terms_hash' => $this->termsHash($termsVersion),
            'early_performance_scope' => $required && $accepted
                ? (string) config('legal.early_performance.scope')
                : null,
            'early_performance_statement_version' => $required && $accepted
                ? (string) config('legal.early_performance.statement_version')
                : null,
            'early_performance_accepted_at' => $required && $accepted ? now() : null,
        ];
    }

    public function statement(): string
    {
        return (string) config('legal.early_performance.statement');
    }

    public function termsHash(string $version): string
    {
        $view = config("legal.terms.versions.{$version}.view");
        $path = is_string($view)
            ? resource_path('views/'.str_replace('.', '/', $view).'.blade.php')
            : null;

        if ($path && is_file($path)) {
            return hash_file('sha256', $path);
        }

        return hash('sha256', $version);
    }
}
