<?php

namespace App\Http\Controllers;

use App\Mail\ProductOrderConfirmationMail;
use App\Models\FormOrder;
use App\Models\OnlinePaymentOrder;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Services\PayNowService;
use App\Services\PayUService;
use App\Services\ProductLegalCheckoutService;
use App\Services\ProductOrderService;
use App\Services\StorefrontCourseAccessService;
use App\Support\OrderFormCustomerProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductCheckoutController extends Controller
{
    public function create(Request $request, Product $product): View
    {
        [$product, $offer] = $this->availableProduct($product);
        $paidPrices = $offer->activePrices->reject(fn (ProductPrice $price) => $price->isComplimentary())->values();
        $requestedId = (int) $request->query('price');
        if ($requestedId > 0) {
            $requested = $offer->activePrices->firstWhere('id', $requestedId);
            abort_unless($requested instanceof ProductPrice && ! $requested->isComplimentary(), 404);
            $selectedPrice = $requested;
        } else {
            $selectedPrice = $paidPrices->first();
        }

        abort_unless($selectedPrice instanceof ProductPrice, 404);

        return view('online-course-storefront.checkout', $this->checkoutViewData(
            $product,
            $offer,
            $prices,
            $selectedPrice
        ));
    }

    public function edit(Product $product, string $ident): View|RedirectResponse
    {
        $order = $this->findProductOrder($product, $ident);
        $product->load(['onlineCourse', 'defaultOffer.activePrices']);
        $offer = $product->defaultOffer;
        abort_unless($offer, 404);

        if ($this->isProductOrderEditLocked($order)) {
            return redirect()
                ->route('online-courses.checkout.summary', $order->ident)
                ->with('error', 'Edycja tego zamówienia jest już wyłączona. Zmiany danych są możliwe po kontakcie z biurem.');
        }

        $prices = $offer->activePrices->reject(fn (ProductPrice $price) => $price->isComplimentary())->values();
        $selectedPrice = $prices->firstWhere('id', (int) $order->orderItems->first()?->product_price_id)
            ?? $prices->first();
        abort_unless($selectedPrice && ! $selectedPrice->isComplimentary(), 404);

        return view('online-course-storefront.checkout', $this->checkoutViewData(
            $product,
            $offer,
            $prices,
            $selectedPrice,
            $order
        ));
    }

    public function store(
        Request $request,
        Product $product,
        ProductOrderService $orders,
        ProductLegalCheckoutService $legal
    ): RedirectResponse {
        [$product, $offer] = $this->availableProduct($product);
        $profile = (string) $request->input('customer_profile', OrderFormCustomerProfile::SCHOOL);
        $buyerType = OrderFormCustomerProfile::buyerTypeForProfile($profile);
        $request->merge(['buyer_type' => $buyerType]);

        $allowedPriceIds = $offer->activePrices
            ->reject(fn (ProductPrice $price) => $price->isComplimentary())
            ->pluck('id')
            ->all();
        $allowedGateways = collect([
            $offer->allow_payu ? 'payu' : null,
            $offer->allow_paynow ? 'paynow' : null,
        ])->filter()->values()->all();
        $paymentTypes = collect([
            $offer->allow_deferred_invoice ? 'deferred' : null,
            $allowedGateways !== [] ? 'online' : null,
        ])->filter()->values()->all();
        $maxParticipants = $buyerType === 'person' || ! $offer->allow_multiple_recipients
            ? 1
            : max(1, (int) config('order_form.max_participants', 50));
        $existingIdent = trim((string) $request->input('order_ident', ''));
        if ($existingIdent !== '') {
            $paymentTypes = array_values(array_unique(array_merge($paymentTypes, ['deferred', 'online'])));
        }

        $validated = $request->validate([
            'order_ident' => ['nullable', 'string', 'max:64'],
            'product_price_id' => ['required', 'integer', Rule::in($allowedPriceIds)],
            'customer_profile' => ['required', Rule::in(OrderFormCustomerProfile::ALL)],
            'buyer_type' => ['required', 'in:person,organisation'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'participants' => ['required', 'array', 'min:1', 'max:'.$maxParticipants],
            'participants.*.first_name' => ['required', 'string', 'max:255'],
            'participants.*.last_name' => ['required', 'string', 'max:255'],
            'participants.*.email' => ['required', 'email', 'max:255', 'distinct:ignore_case'],
            'buyer_name' => ['nullable', 'required_if:buyer_type,organisation', 'string', 'max:500'],
            'buyer_nip' => ['nullable', 'required_if:buyer_type,organisation', 'string', 'max:50'],
            'buyer_person_first_name' => ['nullable', 'required_if:buyer_type,person', 'string', 'max:255'],
            'buyer_person_last_name' => ['nullable', 'required_if:buyer_type,person', 'string', 'max:255'],
            'buyer_address' => ['required', 'string', 'max:500'],
            'buyer_postcode' => ['required', 'string', 'max:50'],
            'buyer_city' => ['required', 'string', 'max:255'],
            'recipient_name' => ['nullable', 'string', 'max:500'],
            'recipient_nip' => ['nullable', 'string', 'max:50'],
            'recipient_address' => ['nullable', 'string', 'max:500'],
            'recipient_postcode' => ['nullable', 'string', 'max:50'],
            'recipient_city' => ['nullable', 'string', 'max:255'],
            'payment_type' => ['required', Rule::in($paymentTypes)],
            'payment_terms' => ['nullable', 'required_if:payment_type,deferred', 'integer', 'min:0', 'max:30'],
            'payment_gateway' => ['nullable', 'required_if:payment_type,online', Rule::in($allowedGateways)],
            'invoice_notes' => ['nullable', 'string', 'max:2000'],
            'early_performance_accepted' => ['nullable', 'boolean'],
        ]);

        $participants = collect($validated['participants'])
            ->map(fn (array $participant) => [
                'first_name' => trim($participant['first_name']),
                'last_name' => trim($participant['last_name']),
                'email' => strtolower(trim($participant['email'])),
            ])
            ->values()
            ->all();

        $price = $offer->activePrices->firstWhere('id', (int) $validated['product_price_id']);
        abort_unless($price instanceof ProductPrice, 422);

        $legal->assertRequiredStatementAccepted(
            $profile,
            $request->input('early_performance_accepted'),
            $price
        );

        $legalEvidence = $legal->evidence(
            $profile,
            $request->boolean('early_performance_accepted'),
            $price
        );
        $paymentLegalEvidence = \Illuminate\Support\Arr::only($legalEvidence, [
            'customer_profile',
            'terms_version',
            'terms_hash',
            'early_performance_scope',
            'early_performance_statement_version',
            'early_performance_accepted_at',
        ]);

        if ($existingIdent !== '') {
            $order = $this->findProductOrder($product, $existingIdent);
            if ($this->isProductOrderEditLocked($order)) {
                return redirect()
                    ->route('online-courses.checkout.summary', $order->ident)
                    ->with('error', 'Edycja tego zamówienia jest już wyłączona. Zmiany danych są możliwe po kontakcie z biurem.');
            }

            $order = $orders->update($order, $product, $price, $validated, $participants, $legalEvidence);

            return redirect()
                ->route('online-courses.checkout.summary', $order->ident)
                ->with('success', 'Dane zamówienia zostały zaktualizowane.');
        }

        $order = $orders->create($product, $price, $validated, $participants, $request, $legalEvidence);
        $this->sendOrderConfirmationSafely($order);

        if ($validated['payment_type'] === 'deferred') {
            return redirect()
                ->route('online-courses.checkout.summary', $order->ident)
                ->with('success', 'Zamówienie zostało złożone. Dostęp zostanie nadany po obsłudze zamówienia przez operatora.');
        }

        return $this->startOnlinePayment($request, $order, $validated, $paymentLegalEvidence);
    }

    public function summary(string $ident): View
    {
        $order = FormOrder::query()
            ->where('ident', $ident)
            ->where('order_kind', 'product')
            ->with(['orderItems.product', 'orderItems.recipients', 'participants', 'onlinePaymentOrders'])
            ->firstOrFail();

        return view('online-course-storefront.order-summary', [
            'order' => $order,
            'orderEditLocked' => $this->isProductOrderEditLocked($order),
        ]);
    }

    private function startOnlinePayment(
        Request $request,
        FormOrder $order,
        array $validated,
        array $legalEvidence
    ): RedirectResponse {
        $item = $order->orderItems->firstOrFail();
        $primary = $item->recipients->firstOrFail();
        $gateway = $validated['payment_gateway'];

        $onlineOrder = OnlinePaymentOrder::query()->create([
            'form_order_id' => $order->id,
            'ident' => OnlinePaymentOrder::generateIdent(),
            'course_id' => null,
            'payment_gateway' => $gateway,
            'status' => OnlinePaymentOrder::STATUS_PENDING,
            'total_amount' => $item->line_total,
            'currency' => $item->currency,
            'buyer_type' => $validated['buyer_type'],
            ...$legalEvidence,
            'email' => $primary->email,
            'first_name' => $primary->first_name,
            'last_name' => $primary->last_name,
            'phone' => $validated['contact_phone'],
            'order_comment' => $validated['invoice_notes'] ?? null,
            'address_data' => [
                'street' => $validated['buyer_address'],
                'postcode' => $validated['buyer_postcode'],
                'city' => $validated['buyer_city'],
                'country' => 'PL',
            ],
            'form_data' => $request->except('_token'),
            'ip_address' => $request->ip(),
        ]);
        $onlineOrder->load(['formOrder.orderItems', 'course']);

        if ($gateway === 'payu') {
            $result = app(PayUService::class)->createOrder(
                $onlineOrder,
                route('payment.payu.notify'),
                route('payment.payu.return')
            );
            $redirectUrl = $result['redirect_uri'] ?? null;
        } else {
            $result = app(PayNowService::class)->createOrder(
                $onlineOrder,
                route('payment.paynow.notify'),
                route('payment.paynow.return')
            );
            $redirectUrl = $result['redirect_url'] ?? null;
        }

        if (! ($result['success'] ?? false) || ! $redirectUrl) {
            $onlineOrder->update(['status' => OnlinePaymentOrder::STATUS_FAILED]);
            $order->update(['payment_status' => FormOrder::PAYMENT_STATUS_FAILED]);

            return redirect()
                ->route('online-courses.checkout.summary', $order->ident)
                ->with('error', $result['error'] ?? 'Nie udało się rozpocząć płatności online.');
        }

        session([
            'payu_order_ident' => $onlineOrder->ident,
            'payu_order_email' => $onlineOrder->email,
        ]);

        return redirect()->away($redirectUrl);
    }

    /**
     * @return array<string, mixed>
     */
    private function checkoutViewData(
        Product $product,
        \App\Models\ProductOffer $offer,
        $prices,
        ProductPrice $selectedPrice,
        ?FormOrder $order = null
    ): array {
        $user = auth()->user();
        $isEditMode = $order !== null;
        $prefill = $isEditMode ? $this->prefillFromOrder($order) : [];

        return [
            'product' => $product,
            'offer' => $offer,
            'prices' => $prices,
            'selectedPrice' => $selectedPrice,
            'order' => $order,
            'isEditMode' => $isEditMode,
            'loggedInUser' => $user,
            'prefill' => $prefill,
            'legalStatement' => app(ProductLegalCheckoutService::class)->statement(),
            'termsVersion' => (string) config('legal.terms.current_version'),
            'viewerAccess' => app(StorefrontCourseAccessService::class)
                ->forCourse($user, (int) $product->resource_id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function prefillFromOrder(FormOrder $order): array
    {
        $item = $order->orderItems->first();
        $buyerName = trim((string) $order->buyer_name);
        $buyerParts = preg_split('/\s+/', $buyerName, 2) ?: [];
        $profile = (string) ($order->customer_profile ?: 'school');

        return [
            'customer_profile' => $profile,
            'contact_name' => $order->orderer_name,
            'contact_email' => $order->orderer_email,
            'contact_phone' => $order->orderer_phone,
            'buyer_name' => $order->buyer_name,
            'buyer_nip' => $order->buyer_nip,
            'buyer_person_first_name' => $buyerParts[0] ?? '',
            'buyer_person_last_name' => $buyerParts[1] ?? '',
            'buyer_address' => $order->buyer_address,
            'buyer_postcode' => $order->buyer_postal_code,
            'buyer_city' => $order->buyer_city,
            'recipient_name' => $order->recipient_name,
            'recipient_nip' => $order->recipient_nip,
            'recipient_address' => $order->recipient_address,
            'recipient_postcode' => $order->recipient_postal_code,
            'recipient_city' => $order->recipient_city,
            'payment_type' => $order->payment_mode === FormOrder::PAYMENT_MODE_ONLINE_GATEWAY ? 'online' : 'deferred',
            'payment_terms' => $order->invoice_payment_delay ?? 14,
            'invoice_notes' => $order->invoice_notes,
            'participants' => $order->participants
                ->sortBy('id')
                ->map(fn ($participant) => [
                    'first_name' => $participant->participant_firstname,
                    'last_name' => $participant->participant_lastname,
                    'email' => $participant->participant_email,
                ])
                ->values()
                ->all() ?: ($item?->recipients->map(fn ($recipient) => [
                    'first_name' => $recipient->first_name,
                    'last_name' => $recipient->last_name,
                    'email' => $recipient->email,
                ])->values()->all() ?? []),
        ];
    }

    private function findProductOrder(Product $product, string $ident): FormOrder
    {
        return FormOrder::query()
            ->where('ident', $ident)
            ->where('order_kind', 'product')
            ->whereHas('orderItems', fn ($query) => $query->where('product_id', $product->id))
            ->with(['orderItems.recipients.fulfillments', 'participants'])
            ->firstOrFail();
    }

    private function isProductOrderEditLocked(FormOrder $order): bool
    {
        if ($order->isEditLocked()) {
            return true;
        }

        return $order->orderItems()
            ->whereHas('recipients.fulfillments', function ($query) {
                $query->whereIn('status', ['succeeded', 'processing']);
            })
            ->exists();
    }

    /**
     * @return array{0: Product, 1: \App\Models\ProductOffer}
     */
    private function availableProduct(Product $product): array
    {
        abort_unless(
            Product::query()
                ->whereKey($product->id)
                ->purchasableOnlineCourses()
                ->whereHas('defaultOffer.activePrices')
                ->exists(),
            404
        );

        $product->load([
            'onlineCourse',
            'defaultOffer.activePrices',
        ]);

        return [$product, $product->defaultOffer];
    }

    private function sendOrderConfirmationSafely(FormOrder $order): void
    {
        try {
            Mail::to($order->orderer_email)->send(new ProductOrderConfirmationMail($order));
            $order->update([
                'legal_confirmation_sent_at' => now('UTC'),
                'legal_confirmation_failed_at' => null,
                'legal_confirmation_error' => null,
            ]);
        } catch (\Throwable $exception) {
            $order->update([
                'legal_confirmation_failed_at' => now('UTC'),
                'legal_confirmation_error' => mb_substr($exception->getMessage(), 0, 1000),
            ]);
            Log::error('Product checkout confirmation mail failed', [
                'form_order_id' => $order->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
