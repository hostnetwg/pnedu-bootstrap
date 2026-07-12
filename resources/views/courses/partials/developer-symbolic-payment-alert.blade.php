@if(!empty($developerSymbolicPayment))
    <div class="alert alert-warning border-warning mb-4 py-2 px-3 small" role="status">
        <strong>Płatność testowa deweloperska:</strong>
        przy wyborze PayU lub PayNow zapłacisz symboliczną kwotę
        <strong>{{ number_format(\App\Support\DeveloperOnlinePaymentTest::SYMBOLIC_AMOUNT_PLN, 2, ',', ' ') }} PLN</strong>
        (pełna cena kursu pozostaje widoczna informacyjnie). Dotyczy wyłącznie Twojego zalogowanego konta deweloperskiego.
    </div>
@endif
