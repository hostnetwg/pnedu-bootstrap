<?php

namespace App\Services;

use App\Models\Course;
use App\Support\OrderFormCustomerProfile;
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

        return app(WithdrawalWindowService::class)->startsWithinWindow(
            $course->start_date,
            $orderedAt
        );
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

        $storeAccepted = $required && $accepted;
        $statement = $this->statement();

        return [
            'customer_profile' => $customerProfile,
            'terms_version' => $termsVersion,
            'terms_hash' => $this->termsHash($termsVersion),
            'contract_concluded_at' => now('UTC'),
            'early_performance_kind' => $storeAccepted
                ? (string) config('legal.early_performance.scope')
                : null,
            'early_performance_scope' => $storeAccepted
                ? (string) config('legal.early_performance.scope')
                : null,
            'early_performance_statement_version' => $storeAccepted
                ? (string) config('legal.early_performance.statement_version')
                : null,
            'early_performance_statement_text' => $storeAccepted ? $statement : null,
            'early_performance_accepted_at' => $storeAccepted ? now() : null,
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
