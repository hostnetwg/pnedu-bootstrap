<?php

namespace App\Services;

use App\Models\ProductPrice;
use App\Support\OrderFormCustomerProfile;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class ProductLegalCheckoutService
{
    public function __construct(
        private readonly WithdrawalWindowService $withdrawalWindow,
        private readonly LegalCheckoutService $legalCheckout,
    ) {}

    public function requiresEarlyPerformanceStatement(
        string $customerProfile,
        ?ProductPrice $price = null,
        ?CarbonInterface $concludedAt = null
    ): bool {
        if (! OrderFormCustomerProfile::isConsumerProtected($customerProfile)) {
            return false;
        }

        return $this->withdrawalWindow->startsWithinWindow(
            $price?->access_starts_at,
            $concludedAt
        );
    }

    public function assertRequiredStatementAccepted(
        string $customerProfile,
        mixed $accepted,
        ?ProductPrice $price = null,
        ?CarbonInterface $concludedAt = null
    ): void {
        if (! $this->requiresEarlyPerformanceStatement($customerProfile, $price, $concludedAt)) {
            return;
        }

        if (config('legal.allow_purchase_without_early_consent')) {
            return;
        }

        if (! filter_var($accepted, FILTER_VALIDATE_BOOLEAN)) {
            throw ValidationException::withMessages([
                'early_performance_accepted' => 'Aby otrzymać dostęp do kursu przed upływem 14 dni od zawarcia umowy, zaznacz wymagane oświadczenie.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function evidence(
        string $customerProfile,
        bool $accepted,
        ?ProductPrice $price = null,
        ?CarbonInterface $concludedAt = null
    ): array {
        $termsVersion = (string) config('legal.terms.current_version');
        $required = $this->requiresEarlyPerformanceStatement($customerProfile, $price, $concludedAt);
        $storeAccepted = $required && $accepted;
        $kind = $this->activeStatementKind();
        $statement = $this->statement();

        return [
            'customer_profile' => $customerProfile,
            'terms_version' => $termsVersion,
            'terms_hash' => $this->legalCheckout->termsHash($termsVersion),
            'contract_concluded_at' => now('UTC'),
            'early_performance_kind' => $storeAccepted ? $kind : null,
            'early_performance_scope' => $storeAccepted ? $kind : null,
            'early_performance_statement_version' => $storeAccepted ? $this->statementVersion() : null,
            'early_performance_statement_text' => $storeAccepted ? $statement : null,
            'early_performance_accepted_at' => $storeAccepted ? now('UTC') : null,
        ];
    }

    public function statement(): string
    {
        if ($this->usesApprovedQualificationStatement()) {
            return (string) config('legal.statements.'.$this->activeStatementKind().'.statement');
        }

        $productStatement = config('legal.product_early_performance.statement');

        return is_string($productStatement) && $productStatement !== ''
            ? $productStatement
            : (string) config('legal.early_performance.statement');
    }

    public function statementVersion(): string
    {
        if ($this->usesApprovedQualificationStatement()) {
            return (string) config('legal.statements.'.$this->activeStatementKind().'.version');
        }

        $productVersion = config('legal.product_early_performance.statement_version');

        return is_string($productVersion) && $productVersion !== ''
            ? $productVersion
            : (string) config('legal.early_performance.statement_version');
    }

    public function activeStatementKind(): string
    {
        if ($this->usesApprovedQualificationStatement()) {
            return (string) config('legal.product_qualification');
        }

        return (string) config('legal.product_early_performance.scope', 'service_and_digital');
    }

    private function usesApprovedQualificationStatement(): bool
    {
        $qualification = (string) config('legal.product_qualification', 'pending');
        if (in_array($qualification, ['', 'pending', 'service_and_digital'], true)) {
            return false;
        }

        return (bool) config("legal.statements.{$qualification}.approved", false);
    }
}
