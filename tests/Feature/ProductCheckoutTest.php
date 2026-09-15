<?php

namespace Tests\Feature;

use App\Models\FormOrder;
use App\Models\Instructor;
use App\Models\OnlineCourse;
use App\Models\OnlinePaymentOrder;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\ProductPrice;
use App\Models\User;
use App\Services\ProductOrderFulfillmentService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected $connectionsToTransact = ['mysql', 'pneadm'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Mail::fake();

        foreach (['products', 'product_offers', 'product_prices', 'order_items', 'order_item_recipients'] as $table) {
            if (! Schema::connection('pneadm')->hasTable($table)) {
                $this->markTestSkipped("Brak tabeli {$table} w bazie pneadm.");
            }
        }
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_public_catalog_offer_and_checkout_are_visible(): void
    {
        [$product, $price] = $this->createOffer();
        if (Schema::connection('pneadm')->hasTable('instructors')) {
            $instructor = Instructor::query()->create([
                'first_name' => 'Łukasz',
                'last_name' => 'Grabowski',
                'email' => 'autor-kursu-'.uniqid().'@example.test',
                'gender' => 'male',
                'is_active' => true,
            ]);
            $product->onlineCourse->forceFill(['instructor_id' => $instructor->id])->save();
        }

        $this->get(route('online-courses.catalog.index'))
            ->assertOk()
            ->assertSee('Kurs sprzedażowy testowy')
            ->assertSee('Autor: Łukasz Grabowski')
            ->assertDontSee('Prowadzący: Łukasz Grabowski')
            ->assertDontSee('pagination.previous')
            ->assertDontSee('Showing 1 to');

        $this->get(route('online-courses.catalog.show', $product->slug))
            ->assertOk()
            ->assertSee('Kurs sprzedażowy testowy')
            ->assertSee('Autor: Łukasz Grabowski')
            ->assertDontSee('Prowadzący: Łukasz Grabowski')
            ->assertSee('Dostęp na rok')
            ->assertSee('Dostęp przez 1 rok')
            ->assertDontSee('od nadania')
            ->assertSee('Zamawiam kurs')
            ->assertDontSee('Zamawiam ten wariant')
            ->assertDontSee('Wybierz dostęp')
            ->assertSee('30 dni gwarancji satysfakcji od rozpoczęcia dostępu do kursu')
            ->assertSee('Zwrot zgłosisz');

        $checkoutHtml = $this->get(route('online-courses.checkout.create', [
            'product' => $product->slug,
            'price' => $price->id,
        ]))
            ->assertOk()
            ->assertSee('id="product-checkout-form"', false)
            ->assertSee('1. Profil')
            ->assertSee('2. Kontakt')
            ->assertSee('3. Faktura')
            ->assertSee('4. Płatność')
            ->assertSee('Kto zamawia kurs?')
            ->assertSee('id="checkout-next"', false)
            ->assertSee('data-checkout-step="2" hidden', false)
            ->assertSee('PayU')
            ->assertSee('Wpisz NIP i pobierz dane z GUS')
            ->assertSee('data-gus-target="buyer"', false)
            ->assertSee('data-gus-target="recipient"', false)
            ->getContent();
        $this->assertMatchesRegularExpression('/id="profilePerson"[^>]*\bchecked\b/', $checkoutHtml);
        $this->assertStringContainsString('Zamawiający jest równocześnie uczestnikiem kursu', $checkoutHtml);
        $this->assertMatchesRegularExpression('/id="participantIsContact"[^>]*\bchecked\b/', $checkoutHtml);
        $this->assertStringContainsString('col-12 col-md-3" id="contactFirstGroup"', $checkoutHtml);
        $this->assertStringContainsString('col-12 col-md-3" id="contactLastGroup"', $checkoutHtml);
        $this->assertStringContainsString('col-12 col-md-3" id="contactEmailGroup"', $checkoutHtml);
        $this->assertStringContainsString('col-12 col-md-3" id="contactPhoneGroup"', $checkoutHtml);
        $this->assertStringContainsString('Telefon kontaktowy', $checkoutHtml);
        $this->assertStringNotContainsString('Telefon — opcjonalnie', $checkoutHtml);
        $this->assertMatchesRegularExpression('/id="contactPhone"[^>]*\brequired\b/', $checkoutHtml);
        $this->assertStringContainsString('data-submitting-text="Wysyłanie zamówienia…"', $checkoutHtml);
    }

    public function test_two_paid_variants_keep_this_variant_button(): void
    {
        [$product] = $this->createOffer();
        ProductPrice::query()->forceCreate([
            'product_offer_id' => $product->defaultOffer->id,
            'name' => 'Dostęp na 2 lata',
            'is_active' => true,
            'sort_order' => 20,
            'price' => '299.00',
            'currency' => 'PLN',
            'tax_treatment' => ProductPrice::TAX_EXEMPT,
            'is_promotion' => false,
            'access_policy' => ProductPrice::ACCESS_DURATION_FROM_GRANT,
            'access_duration_value' => 2,
            'access_duration_unit' => 'years',
        ]);

        $this->get(route('online-courses.catalog.show', $product->slug))
            ->assertOk()
            ->assertSee('Wybierz dostęp')
            ->assertSee('Zamawiam ten wariant')
            ->assertDontSee('Zamawiam kurs');
    }

    public function test_checkout_copy_stays_short_without_terms_checkbox(): void
    {
        [$product, $price] = $this->createOffer();
        $statement = (string) config('legal.product_early_performance.statement');

        $html = $this->get(route('online-courses.checkout.create', [
            'product' => $product->slug,
            'price' => $price->id,
        ]))->assertOk()->getContent();

        $this->assertStringContainsString('id="checkoutWithdrawalInfo" hidden', $html);
        $this->assertStringContainsString('id="earlyPerformanceWrap" hidden', $html);
        $this->assertStringContainsString('Co do zasady masz 14 dni od zawarcia umowy na odstąpienie.', $html);
        $this->assertStringContainsString('Odstąpienie od umowy', $html);
        $this->assertStringContainsString(route('withdrawal'), $html);
        $this->assertStringNotContainsString('Jeżeli kupujesz jako konsument lub przedsiębiorca', $html);
        $this->assertStringContainsString($statement, $html);
        $this->assertStringNotContainsString('realizacji szkolenia', $html);
        $this->assertStringContainsString('30 dni gwarancji satysfakcji od rozpoczęcia dostępu do kursu', $html);
        $this->assertStringContainsString('Zwrot zgłosisz', $html);
        $this->assertStringContainsString('mailto:kontakt@pnedu.pl', $html);
        $this->assertStringContainsString('tel:+48501654274', $html);
        $this->assertStringNotContainsString('Prośbę o zwrot', $html);
        $this->assertStringNotContainsString('automatycznie', $html);
        $this->assertStringNotContainsString('pełna kwota', $html);
        $this->assertStringContainsString('Potwierdzam zakup', $html);
        $this->assertStringNotContainsString('Tryb developerski', $html);
        $this->assertSame(1, substr_count($html, 'name="early_performance_accepted"'));
        $this->assertDoesNotMatchRegularExpression('/id="earlyPerformanceAccepted"[^>]*\bchecked\b/', $html);
        $this->assertStringNotContainsString('akceptuję regulamin', mb_strtolower($html));
    }

    public function test_guarantee_copy_uses_configured_days(): void
    {
        [$product, $price] = $this->createOffer();
        $price->offer->forceFill(['satisfaction_guarantee_days' => 14])->save();

        $this->get(route('online-courses.catalog.show', $product->slug))
            ->assertOk()
            ->assertSee('14 dni gwarancji satysfakcji od rozpoczęcia dostępu do kursu')
            ->assertDontSee('30 dni gwarancji satysfakcji');
    }

    public function test_catalog_shows_promotion_end_omnibus_and_countdown(): void
    {
        [$product, $price] = $this->createOffer();
        $price->forceFill([
            'is_promotion' => true,
            'promotion_price' => '149.00',
            'promotion_ends_at' => CarbonImmutable::parse('2026-10-01 15:00:00', 'UTC'),
            'show_promotion_countdown' => true,
        ])->save();

        $this->get(route('online-courses.catalog.index'))
            ->assertOk()
            ->assertSee('Promocja trwa do:')
            ->assertSee('Najniższa cena z 30 dni przed obniżką')
            ->assertSee('199,00 PLN')
            ->assertSee('Do końca promocji:')
            ->assertSee('data-promotion-countdown', false);

        $this->get(route('online-courses.catalog.show', $product->slug))
            ->assertOk()
            ->assertSee('Promocja trwa do:')
            ->assertSee('Do końca promocji:');

        $this->get(route('online-courses.checkout.create', [
            'product' => $product->slug,
            'price' => $price->id,
        ]))
            ->assertOk()
            ->assertSee('Promocja trwa do:')
            ->assertSee('Do końca promocji:');
    }

    public function test_deferred_checkout_creates_snapshot_item_and_recipients(): void
    {
        [$product, $price] = $this->createOffer();

        $response = $this->post(route('online-courses.checkout.store', $product->slug), [
            'product_price_id' => $price->id,
            'customer_profile' => 'school',
            'contact_name' => 'Szkoła Testowa',
            'contact_email' => 'sekretariat@example.test',
            'contact_phone' => '501002003',
            'participants' => [
                ['first_name' => 'Anna', 'last_name' => 'Nowak', 'email' => 'anna@example.test'],
                ['first_name' => 'Jan', 'last_name' => 'Kowalski', 'email' => 'jan@example.test'],
            ],
            'buyer_name' => 'Gmina Testowa',
            'buyer_nip' => '1234567890',
            'buyer_address' => 'ul. Testowa 1',
            'buyer_postcode' => '00-001',
            'buyer_city' => 'Warszawa',
            'payment_type' => 'deferred',
            'payment_terms' => 14,
        ]);

        $orderId = (int) DB::connection('pneadm')
            ->table('form_orders')
            ->where('order_kind', 'product')
            ->where('orderer_email', 'sekretariat@example.test')
            ->value('id');

        $response->assertRedirect();
        $this->assertGreaterThan(0, $orderId);
        $this->assertDatabaseHas('form_orders', [
            'id' => $orderId,
            'order_kind' => 'product',
            'product_id' => null,
            'product_price' => '398.00',
            'payment_mode' => 'deferred_invoice',
        ], 'pneadm');
        $this->assertDatabaseHas('order_items', [
            'form_order_id' => $orderId,
            'product_id' => $product->id,
            'product_price_id' => $price->id,
            'unit_price' => '199.00',
            'quantity' => 2,
            'line_total' => '398.00',
            'satisfaction_guarantee_days' => 30,
        ], 'pneadm');
        $this->assertSame(
            2,
            DB::connection('pneadm')->table('order_item_recipients')
                ->join('order_items', 'order_items.id', '=', 'order_item_recipients.order_item_id')
                ->where('order_items.form_order_id', $orderId)
                ->count()
        );

        $order = FormOrder::query()->findOrFail($orderId);
        $this->get(route('online-courses.checkout.summary', $order->ident))
            ->assertOk()
            ->assertSee('Numer zamówienia:')
            ->assertSee((string) $order->id)
            ->assertSee('EDYTUJ')
            ->assertSee('Potwierdzenie zamówienia (PDF)')
            ->assertSee(route('orders.pdf', ['ident' => $order->ident]), false);

        $this->get(route('orders.pdf', ['ident' => $order->ident]))->assertOk();

        $this->get(route('online-courses.checkout.edit', [
            'product' => $product->slug,
            'ident' => $order->ident,
        ]))
            ->assertOk()
            ->assertSee('Edytuj zamówienie kursu online')
            ->assertSee('sekretariat@example.test');

        $this->post(route('online-courses.checkout.store', $product->slug), [
            'order_ident' => $order->ident,
            'product_price_id' => $price->id,
            'customer_profile' => 'school',
            'contact_name' => 'Szkoła Poprawiona',
            'contact_email' => 'sekretariat@example.test',
            'contact_phone' => '501002003',
            'participants' => [
                ['first_name' => 'Anna', 'last_name' => 'Nowak', 'email' => 'anna@example.test'],
                ['first_name' => 'Jan', 'last_name' => 'Kowalski', 'email' => 'jan@example.test'],
            ],
            'buyer_name' => 'Gmina Testowa',
            'buyer_nip' => '1234567890',
            'buyer_address' => 'ul. Testowa 1',
            'buyer_postcode' => '00-001',
            'buyer_city' => 'Warszawa',
            'payment_type' => 'deferred',
            'payment_terms' => 14,
        ])->assertRedirect(route('online-courses.checkout.summary', $order->ident));

        $this->assertDatabaseHas('form_orders', [
            'id' => $order->id,
            'orderer_name' => 'Szkoła Poprawiona',
        ], 'pneadm');
    }

    public function test_logged_in_checkout_prefills_participant_email_and_shows_account_warning(): void
    {
        [$product, $price] = $this->createOffer();
        $user = User::factory()->create([
            'first_name' => 'Maria',
            'last_name' => 'Kowalska',
            'email' => 'maria.konto@example.test',
        ]);

        $this->actingAs($user)
            ->get(route('online-courses.checkout.create', [
                'product' => $product->slug,
                'price' => $price->id,
            ]))
            ->assertOk()
            ->assertSee('value="maria.konto@example.test"', false)
            ->assertSee('value="Maria"', false)
            ->assertSee('id="loggedInEmailWarning"', false)
            ->assertSee('Jesteś zalogowany jako');
    }

    public function test_manual_fulfillment_grants_access_once_and_creates_account(): void
    {
        Notification::fake();
        CarbonImmutable::setTestNow('2026-09-12 10:00:00');
        [$product, $price] = $this->createOffer();

        $this->post(route('online-courses.checkout.store', $product->slug), [
            'product_price_id' => $price->id,
            'customer_profile' => 'school',
            'contact_name' => 'Szkoła Testowa',
            'contact_email' => 'sekretariat-fulfillment@example.test',
            'contact_phone' => '501002003',
            'participants' => [
                ['first_name' => 'Maria', 'last_name' => 'Testowa', 'email' => 'maria-fulfillment@example.test'],
            ],
            'buyer_name' => 'Gmina Testowa',
            'buyer_nip' => '1234567890',
            'buyer_address' => 'ul. Testowa 1',
            'buyer_postcode' => '00-001',
            'buyer_city' => 'Warszawa',
            'payment_type' => 'deferred',
            'payment_terms' => 14,
        ])->assertRedirect();

        $order = FormOrder::query()
            ->where('orderer_email', 'sekretariat-fulfillment@example.test')
            ->latest('id')
            ->firstOrFail();
        $service = app(ProductOrderFulfillmentService::class);

        $first = $service->fulfillOrder($order->id, 'manual');
        $second = $service->fulfillOrder($order->id, 'manual');
        $fulfillmentError = (string) DB::connection('pneadm')
            ->table('order_fulfillments')
            ->value('error_message');

        $this->assertTrue($first['success'], $first['message'].' '.$fulfillmentError);
        $this->assertSame(1, $first['fulfilled']);
        $this->assertSame(0, $first['skipped']);
        $this->assertTrue($second['success']);
        $this->assertSame(0, $second['fulfilled']);
        $this->assertSame(1, $second['skipped']);
        $this->assertDatabaseHas('users', ['email' => 'maria-fulfillment@example.test'], 'mysql');
        $this->assertDatabaseHas('online_course_enrollments', [
            'online_course_id' => $product->resource_id,
            'email' => 'maria-fulfillment@example.test',
            'access_source' => 'product_order',
            'access_expires_at' => '2027-09-12 08:00:00',
        ], 'pneadm');
        $this->assertDatabaseHas('order_fulfillments', [
            'status' => 'succeeded',
        ], 'pneadm');
        $this->assertNotNull($order->fresh()->pnedu_provisioned_at);

        $user = User::query()->where('email', 'maria-fulfillment@example.test')->firstOrFail();
        Notification::assertSentTo($user, \App\Notifications\OnlineCourseProductAccessGranted::class);
    }

    public function test_revoke_then_regrant_after_email_fix_creates_new_enrollment(): void
    {
        Notification::fake();
        CarbonImmutable::setTestNow('2026-09-12 10:00:00');
        [$product, $price] = $this->createOffer();

        $this->post(route('online-courses.checkout.store', $product->slug), [
            'product_price_id' => $price->id,
            'customer_profile' => 'school',
            'contact_name' => 'Szkoła Testowa',
            'contact_email' => 'sekretariat-revoke@example.test',
            'contact_phone' => '501002003',
            'participants' => [
                ['first_name' => 'Maria', 'last_name' => 'Testowa', 'email' => 'maria-typo@example.test'],
            ],
            'buyer_name' => 'Gmina Testowa',
            'buyer_nip' => '1234567890',
            'buyer_address' => 'ul. Testowa 1',
            'buyer_postcode' => '00-001',
            'buyer_city' => 'Warszawa',
            'payment_type' => 'deferred',
            'payment_terms' => 14,
        ])->assertRedirect();

        $order = FormOrder::query()
            ->where('orderer_email', 'sekretariat-revoke@example.test')
            ->latest('id')
            ->firstOrFail();
        $service = app(ProductOrderFulfillmentService::class);
        $this->assertTrue($service->fulfillOrder($order->id, 'manual')['success']);

        $recipient = $order->fresh(['orderItems.recipients'])->orderItems->first()->recipients->first();
        $revoked = $service->revokeOrder($order->id, $recipient->id);
        $this->assertTrue($revoked['success'], $revoked['message']);
        $this->assertSame(1, $revoked['revoked']);
        $this->assertDatabaseMissing('online_course_enrollments', [
            'email' => 'maria-typo@example.test',
        ], 'pneadm');
        $this->assertDatabaseHas('users', ['email' => 'maria-typo@example.test'], 'mysql');
        $this->assertNull($order->fresh()->pnedu_provisioned_at);

        $recipient->update(['email' => 'maria-poprawiona@example.test']);
        $regranted = $service->fulfillOrder($order->id, 'manual', $recipient->id);
        $this->assertTrue($regranted['success'], $regranted['message']);
        $this->assertSame(1, $regranted['fulfilled']);
        $this->assertDatabaseHas('online_course_enrollments', [
            'online_course_id' => $product->resource_id,
            'email' => 'maria-poprawiona@example.test',
        ], 'pneadm');
        $this->assertDatabaseHas('users', ['email' => 'maria-poprawiona@example.test'], 'mysql');
        $this->assertNotNull($order->fresh()->pnedu_provisioned_at);
    }

    public function test_payu_checkout_creates_generic_payment_without_course_id(): void
    {
        Notification::fake();
        config([
            'services.payu.sandbox' => false,
            'services.payu.client_id' => 'client-id',
            'services.payu.client_secret' => 'client-secret',
            'services.payu.pos_id' => 'pos-id',
        ]);
        Http::fake([
            'https://secure.payu.com/pl/standard/user/oauth/authorize' => Http::response([
                'access_token' => 'test-token',
            ]),
            'https://secure.payu.com/api/v2_1/orders' => Http::response([
                'redirectUri' => 'https://payu.example.test/payment',
                'orderId' => 'PAYU-123',
            ], 201),
        ]);
        [$product, $price] = $this->createOffer();

        $this->post(route('online-courses.checkout.store', $product->slug), array_merge(
            $this->checkoutPayload($price),
            ['payment_type' => 'online', 'payment_gateway' => 'payu']
        ))->assertRedirect('https://payu.example.test/payment');

        $this->assertDatabaseHas('online_payment_orders', [
            'course_id' => null,
            'payment_gateway' => 'payu',
            'status' => 'created',
            'total_amount' => '199.00',
        ], 'pneadm');
        Http::assertSent(fn ($request) => $request->url() === 'https://secure.payu.com/api/v2_1/orders'
            && $request['products'][0]['name'] === 'Kurs sprzedażowy testowy'
            && $request['products'][0]['quantity'] === '1');

        $onlineOrder = OnlinePaymentOrder::query()->latest('id')->firstOrFail();
        $this->postJson(route('payment.payu.notify'), [
            'order' => [
                'orderId' => 'PAYU-123',
                'extOrderId' => $onlineOrder->ident,
                'status' => 'COMPLETED',
            ],
        ])->assertOk();

        $this->assertDatabaseHas('online_payment_orders', [
            'id' => $onlineOrder->id,
            'status' => OnlinePaymentOrder::STATUS_PAID,
        ], 'pneadm');
        $this->assertDatabaseHas('order_fulfillments', [
            'status' => 'succeeded',
        ], 'pneadm');
        $this->assertNotNull($onlineOrder->formOrder?->fresh()->pnedu_provisioned_at);
    }

    public function test_paynow_checkout_creates_generic_payment_without_course_id(): void
    {
        config([
            'services.paynow.sandbox' => false,
            'services.paynow.api_key' => 'api-key',
            'services.paynow.signature_key' => 'signature-key',
        ]);
        Http::fake([
            'https://api.paynow.pl/v3/payments' => Http::response([
                'redirectUrl' => 'https://paynow.example.test/payment',
                'paymentId' => 'PAYNOW-123',
            ], 201),
        ]);
        [$product, $price] = $this->createOffer();

        $this->post(route('online-courses.checkout.store', $product->slug), array_merge(
            $this->checkoutPayload($price),
            ['payment_type' => 'online', 'payment_gateway' => 'paynow']
        ))->assertRedirect('https://paynow.example.test/payment');

        $this->assertDatabaseHas('online_payment_orders', [
            'course_id' => null,
            'payment_gateway' => 'paynow',
            'status' => 'created',
            'total_amount' => '199.00',
        ], 'pneadm');
        Http::assertSent(fn ($request) => $request->url() === 'https://api.paynow.pl/v3/payments');
    }

    public function test_person_checkout_requires_waiver_for_immediate_access(): void
    {
        [$product, $price] = $this->createOffer();

        $this->from(route('online-courses.checkout.create', [
            'product' => $product->slug,
            'price' => $price->id,
        ]))
            ->post(
                route('online-courses.checkout.store', $product->slug),
                $this->personCheckoutPayload($price)
            )
            ->assertRedirect()
            ->assertSessionHasErrors('early_performance_accepted');
    }

    public function test_person_presale_checkout_skips_waiver_and_snapshots_start(): void
    {
        [$product, $price] = $this->createOffer();
        $price->update([
            'access_starts_at' => CarbonImmutable::parse('2026-12-01 08:00:00', 'UTC'),
            'access_note' => 'Materiały publikujemy partiami',
        ]);

        $payload = $this->personCheckoutPayload($price);
        $this->post(route('online-courses.checkout.store', $product->slug), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('form_orders', [
            'orderer_email' => $payload['contact_email'],
            'order_kind' => 'product',
            'early_performance_accepted_at' => null,
        ], 'pneadm');
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'access_note' => 'Materiały publikujemy partiami',
            'satisfaction_guarantee_days' => 30,
        ], 'pneadm');

        $item = OrderItem::query()->where('product_id', $product->id)->latest('id')->first();
        $this->assertNotNull($item);
        $price->refresh();
        $this->assertTrue($price->accessStartsInFuture());
        $this->assertTrue($item->access_starts_at?->equalTo($price->access_starts_at));
    }

    public function test_person_checkout_composes_contact_name_from_first_and_last(): void
    {
        [$product, $price] = $this->createOffer();
        $price->update([
            'access_starts_at' => CarbonImmutable::parse('2026-12-01 08:00:00', 'UTC'),
        ]);

        $payload = $this->personCheckoutPayload($price);
        unset($payload['contact_name']);
        $payload['contact_first_name'] = 'Anna';
        $payload['contact_last_name'] = 'Prywatna';
        $payload['participant_is_contact'] = '1';

        $this->post(route('online-courses.checkout.store', $product->slug), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('form_orders', [
            'orderer_email' => $payload['contact_email'],
            'orderer_name' => 'Anna Prywatna',
            'order_kind' => 'product',
        ], 'pneadm');
    }

    public function test_person_presale_in_three_days_requires_waiver(): void
    {
        CarbonImmutable::setTestNow('2026-09-13 12:00:00');
        [$product, $price] = $this->createOffer();
        $price->update([
            'access_starts_at' => CarbonImmutable::parse('2026-09-16 08:00:00', 'UTC'),
        ]);

        $this->from(route('online-courses.checkout.create', [
            'product' => $product->slug,
            'price' => $price->id,
        ]))
            ->post(route('online-courses.checkout.store', $product->slug), $this->personCheckoutPayload($price))
            ->assertSessionHasErrors('early_performance_accepted');
    }

    public function test_contact_phone_is_required(): void
    {
        [$product, $price] = $this->createOffer();
        $payload = $this->personCheckoutPayload($price);
        $payload['contact_phone'] = '';

        $this->from(route('online-courses.checkout.create', [
            'product' => $product->slug,
            'price' => $price->id,
        ]))
            ->post(route('online-courses.checkout.store', $product->slug), $payload)
            ->assertRedirect()
            ->assertSessionHasErrors('contact_phone');
    }

    public function test_person_waiver_stores_exact_statement(): void
    {
        [$product, $price] = $this->createOffer();
        $payload = $this->personCheckoutPayload($price);
        $payload['early_performance_accepted'] = '1';

        $this->post(route('online-courses.checkout.store', $product->slug), $payload)
            ->assertRedirect();

        $order = FormOrder::query()->where('orderer_email', $payload['contact_email'])->latest('id')->firstOrFail();
        $statement = (string) config('legal.product_early_performance.statement');
        $this->assertNotNull($order->early_performance_accepted_at);
        $this->assertSame($statement, $order->early_performance_statement_text);
        $this->assertSame('2026-09-13-course-v1', $order->early_performance_statement_version);
        $this->assertSame($order->early_performance_statement_text, app(\App\Services\ProductLegalCheckoutService::class)->statement());
        $this->assertSame(30, (int) $order->orderItems->first()->satisfaction_guarantee_days);
        Mail::assertSent(\App\Mail\ProductOrderConfirmationMail::class);
    }

    public function test_confirmation_keeps_historical_statement_text(): void
    {
        [$product, $price] = $this->createOffer();
        $payload = $this->personCheckoutPayload($price);
        $payload['early_performance_accepted'] = '1';

        $this->post(route('online-courses.checkout.store', $product->slug), $payload)
            ->assertRedirect();

        $order = FormOrder::query()->where('orderer_email', $payload['contact_email'])->latest('id')->firstOrFail();
        $historical = 'Historyczne oświadczenie z zamówienia 2026-09-08-v2.';
        $order->forceFill([
            'early_performance_statement_text' => $historical,
            'early_performance_statement_version' => '2026-09-08-v2',
        ])->save();
        $order->loadMissing(['orderItems.recipients', 'participants']);

        $html = view('emails.product-order-confirmation', ['order' => $order])->render();
        $this->assertStringContainsString($historical, $html);
        $this->assertStringNotContainsString((string) config('legal.product_early_performance.statement'), $html);

        $specification = view('legal.product-order-specification', [
            'order' => $order,
            'item' => $order->orderItems->first(),
        ])->render();
        $this->assertStringContainsString($historical, $specification);
        $this->assertStringNotContainsString((string) config('legal.product_early_performance.statement'), $specification);
    }

    public function test_jdg_with_nip_keeps_consumer_protection(): void
    {
        [$product, $price] = $this->createOffer();

        $this->from(route('online-courses.checkout.create', $product->slug))
            ->post(route('online-courses.checkout.store', $product->slug), [
                'product_price_id' => $price->id,
                'customer_profile' => 'jdg',
                'contact_name' => 'Jan Jdg',
                'contact_email' => 'jdg-'.uniqid().'@example.test',
                'contact_phone' => '501002003',
                'participants' => [
                    ['first_name' => 'Jan', 'last_name' => 'Jdg', 'email' => 'jdg-u-'.uniqid().'@example.test'],
                ],
                'buyer_name' => 'Jan Jdg',
                'buyer_nip' => '1234567890',
                'buyer_address' => 'ul. Testowa 1',
                'buyer_postcode' => '00-001',
                'buyer_city' => 'Warszawa',
                'payment_type' => 'deferred',
                'payment_terms' => 14,
            ])
            ->assertSessionHasErrors('early_performance_accepted');
    }

    public function test_later_guarantee_change_does_not_alter_order_snapshot(): void
    {
        [$product, $price] = $this->createOffer();
        $payload = $this->personCheckoutPayload($price);
        $payload['early_performance_accepted'] = '1';
        $this->post(route('online-courses.checkout.store', $product->slug), $payload)->assertRedirect();

        $price->offer->forceFill(['satisfaction_guarantee_days' => 7])->save();
        $item = OrderItem::query()->where('product_id', $product->id)->latest('id')->firstOrFail();
        $this->assertSame(30, (int) $item->satisfaction_guarantee_days);
    }

    public function test_repeat_deferred_checkout_reuses_unpaid_order_and_sends_one_mail(): void
    {
        [$product, $price] = $this->createOffer();
        $payload = $this->personCheckoutPayload($price);
        $payload['early_performance_accepted'] = '1';

        $this->post(route('online-courses.checkout.store', $product->slug), $payload)->assertRedirect();
        $this->post(route('online-courses.checkout.store', $product->slug), $payload)->assertRedirect();

        $this->assertSame(1, FormOrder::query()->where('orderer_email', $payload['contact_email'])->count());
        Mail::assertSent(\App\Mail\ProductOrderConfirmationMail::class, 1);
    }

    public function test_repeat_online_checkout_reuses_unpaid_order(): void
    {
        Notification::fake();
        config([
            'services.payu.sandbox' => false,
            'services.payu.client_id' => 'client-id',
            'services.payu.client_secret' => 'client-secret',
            'services.payu.pos_id' => 'pos-id',
        ]);
        Http::fake([
            'https://secure.payu.com/pl/standard/user/oauth/authorize' => Http::response([
                'access_token' => 'test-token',
            ]),
            'https://secure.payu.com/api/v2_1/orders' => Http::response([
                'redirectUri' => 'https://payu.example.test/payment',
                'orderId' => 'PAYU-REPEAT',
            ], 201),
        ]);
        [$product, $price] = $this->createOffer();
        $payload = array_merge($this->checkoutPayload($price), [
            'payment_type' => 'online',
            'payment_gateway' => 'payu',
        ]);

        $this->post(route('online-courses.checkout.store', $product->slug), $payload)
            ->assertRedirect('https://payu.example.test/payment');
        $this->post(route('online-courses.checkout.store', $product->slug), $payload)
            ->assertRedirect('https://payu.example.test/payment');

        $this->assertSame(1, FormOrder::query()->where('orderer_email', $payload['contact_email'])->count());
        $this->assertSame(2, OnlinePaymentOrder::query()->where('email', $payload['participants'][0]['email'])->count());
        Mail::assertSent(\App\Mail\ProductOrderConfirmationMail::class, 1);

        $this->get(route('online-courses.checkout.create', [
            'product' => $product->slug,
            'price' => $price->id,
        ]))->assertOk()->assertSee('name="order_ident"', false);
    }

    public function test_failed_payu_keeps_one_order_and_skips_confirmation(): void
    {
        config([
            'services.payu.sandbox' => false,
            'services.payu.client_id' => 'client-id',
            'services.payu.client_secret' => 'client-secret',
            'services.payu.pos_id' => 'pos-id',
        ]);
        Http::fake([
            'https://secure.payu.com/pl/standard/user/oauth/authorize' => Http::response([
                'access_token' => 'test-token',
            ]),
            'https://secure.payu.com/api/v2_1/orders' => Http::response(['status' => ['statusDesc' => 'Rejected']], 400),
        ]);
        [$product, $price] = $this->createOffer();
        $payload = array_merge($this->checkoutPayload($price), [
            'payment_type' => 'online',
            'payment_gateway' => 'payu',
        ]);

        $this->from(route('online-courses.checkout.create', [
            'product' => $product->slug,
            'price' => $price->id,
        ]))->post(route('online-courses.checkout.store', $product->slug), $payload)
            ->assertRedirect(route('online-courses.checkout.create', [
                'product' => $product->slug,
                'price' => $price->id,
            ]))
            ->assertSessionHas('error');

        $this->assertSame(1, FormOrder::query()->where('orderer_email', $payload['contact_email'])->count());
        Mail::assertNothingSent();

        $this->post(route('online-courses.checkout.store', $product->slug), $payload)
            ->assertRedirect(route('online-courses.checkout.create', [
                'product' => $product->slug,
                'price' => $price->id,
            ]));

        $this->assertSame(1, FormOrder::query()->where('orderer_email', $payload['contact_email'])->count());
        $this->assertSame(2, OnlinePaymentOrder::query()->where('email', $payload['participants'][0]['email'])->count());
        Mail::assertNothingSent();
    }

    /**
     * @return array{0: Product, 1: ProductPrice}
     */
    private function createOffer(): array
    {
        $course = OnlineCourse::query()->create([
            'slug' => 'kurs-sprzedazowy-testowy-'.uniqid(),
            'title' => 'Kurs sprzedażowy testowy',
            'description' => 'Opis kursu do testu publicznej oferty.',
            'is_active' => true,
            'visible_in_dashboard' => true,
        ]);
        $product = Product::query()->create([
            'type' => Product::TYPE_ONLINE_COURSE,
            'resource_id' => $course->id,
            'name' => 'Kurs sprzedażowy testowy',
            'slug' => 'kurs-sprzedazowy-testowy-'.uniqid(),
            'fulfillment_type' => Product::FULFILLMENT_ONLINE_COURSE_ACCESS,
            'is_active' => true,
            'requires_shipping' => false,
        ]);
        $offer = ProductOffer::query()->forceCreate([
            'product_id' => $product->id,
            'code' => ProductOffer::DEFAULT_CODE,
            'sales_channel' => ProductOffer::CHANNEL_PNEDU,
            'is_active' => true,
            'is_public' => true,
            'allow_multiple_recipients' => true,
            'allow_deferred_invoice' => true,
            'allow_payu' => true,
            'allow_paynow' => true,
            'satisfaction_guarantee_days' => 30,
            'sort_order' => 10,
        ]);
        $price = ProductPrice::query()->forceCreate([
            'product_offer_id' => $offer->id,
            'name' => 'Dostęp na rok',
            'is_active' => true,
            'sort_order' => 10,
            'price' => '199.00',
            'currency' => 'PLN',
            'tax_treatment' => ProductPrice::TAX_EXEMPT,
            'is_promotion' => false,
            'access_policy' => ProductPrice::ACCESS_DURATION_FROM_GRANT,
            'access_duration_value' => 1,
            'access_duration_unit' => 'years',
        ]);

        return [$product, $price];
    }

    /**
     * @return array<string, mixed>
     */
    private function checkoutPayload(ProductPrice $price): array
    {
        return [
            'product_price_id' => $price->id,
            'customer_profile' => 'school',
            'contact_name' => 'Szkoła Płatności',
            'contact_email' => 'platnosci-'.uniqid().'@example.test',
            'contact_phone' => '501002003',
            'participants' => [
                ['first_name' => 'Anna', 'last_name' => 'Płatnicza', 'email' => 'uczestnik-'.uniqid().'@example.test'],
            ],
            'buyer_name' => 'Gmina Testowa',
            'buyer_nip' => '1234567890',
            'buyer_address' => 'ul. Testowa 1',
            'buyer_postcode' => '00-001',
            'buyer_city' => 'Warszawa',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function personCheckoutPayload(ProductPrice $price): array
    {
        return [
            'product_price_id' => $price->id,
            'customer_profile' => 'person',
            'contact_name' => 'Anna Prywatna',
            'contact_email' => 'osoba-'.uniqid().'@example.test',
            'contact_phone' => '501002003',
            'participants' => [
                ['first_name' => 'Anna', 'last_name' => 'Prywatna', 'email' => 'uczestnik-osoba-'.uniqid().'@example.test'],
            ],
            'buyer_person_first_name' => 'Anna',
            'buyer_person_last_name' => 'Prywatna',
            'buyer_address' => 'ul. Testowa 1',
            'buyer_postcode' => '00-001',
            'buyer_city' => 'Warszawa',
            'payment_type' => 'deferred',
            'payment_terms' => 14,
        ];
    }
}
