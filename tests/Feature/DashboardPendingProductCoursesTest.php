<?php

namespace Tests\Feature;

use App\Models\FormOrder;
use App\Models\OnlineCourse;
use App\Models\OnlineCourseEnrollment;
use App\Models\OnlinePaymentOrder;
use App\Models\OrderItemRecipient;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\ProductPrice;
use App\Models\User;
use App\Services\ProductOrderFulfillmentService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardPendingProductCoursesTest extends TestCase
{
    use RefreshDatabase;

    protected $connectionsToTransact = ['mysql', 'pneadm'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        foreach (['products', 'product_offers', 'product_prices', 'order_items', 'order_item_recipients'] as $table) {
            if (! Schema::connection('pneadm')->hasTable($table)) {
                $this->markTestSkipped("Brak tabeli {$table} w bazie pneadm.");
            }
        }
    }

    public function test_only_participant_email_sees_deferred_pending_card(): void
    {
        [$product, $price] = $this->createOffer();
        $order = $this->createDeferredOrder($product, $price, 'anna-dashboard@example.test', 'sekretariat-dashboard@example.test');

        $participant = User::factory()->create(['email' => 'anna-dashboard@example.test']);
        $orderer = User::factory()->create(['email' => 'sekretariat-dashboard@example.test']);

        $this->actingAs($participant)
            ->get(route('dashboard.online-courses.index'))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee('Zamówienie zostało przyjęte')
            ->assertSee((string) $order->id)
            ->assertDontSee('Dokończ płatność')
            ->assertDontSee('Anuluję / rezygnuję z zamówienia');

        $this->actingAs($orderer)
            ->get(route('dashboard.online-courses.index'))
            ->assertOk()
            ->assertDontSee('Zamówienie zostało przyjęte')
            ->assertDontSee('Dokończ płatność');
    }

    public function test_unpaid_online_order_shows_finish_payment_and_resign(): void
    {
        [$product, $price] = $this->createOffer();
        $order = $this->createDeferredOrder($product, $price, 'piotr-dashboard@example.test');
        $this->markOrderAwaitingOnlinePayment($order);

        $user = User::factory()->create(['email' => 'piotr-dashboard@example.test']);

        $this->actingAs($user)
            ->get(route('dashboard.online-courses.index'))
            ->assertOk()
            ->assertSee('Dokończ płatność')
            ->assertSee('Anuluję / rezygnuję z zamówienia')
            ->assertSee(route('payment.pending', 'OPO-DASH-1'), false)
            ->assertDontSee('Zamówienie zostało przyjęte. Dostęp nadamy');
    }

    public function test_paid_but_not_fulfilled_hides_payment_messages(): void
    {
        [$product, $price] = $this->createOffer();
        $order = $this->createDeferredOrder($product, $price, 'paid-wait@example.test');
        $order->update([
            'payment_mode' => FormOrder::PAYMENT_MODE_ONLINE_GATEWAY,
            'payment_status' => FormOrder::PAYMENT_STATUS_PAID,
        ]);

        $user = User::factory()->create(['email' => 'paid-wait@example.test']);

        $this->actingAs($user)
            ->get(route('dashboard.online-courses.index'))
            ->assertOk()
            ->assertSee('Zamówienie zostało przyjęte')
            ->assertDontSee('Dokończ płatność')
            ->assertDontSee('Anuluję / rezygnuję z zamówienia');
    }

    public function test_fulfilled_order_shows_course_instead_of_pending_card(): void
    {
        Notification::fake();
        [$product, $price] = $this->createOffer();
        $order = $this->createDeferredOrder($product, $price, 'maria-granted@example.test');
        $this->assertTrue(app(ProductOrderFulfillmentService::class)->fulfillOrder($order->id, 'manual')['success']);

        $user = User::query()->where('email', 'maria-granted@example.test')->firstOrFail();

        $this->actingAs($user)
            ->get(route('dashboard.online-courses.index'))
            ->assertOk()
            ->assertSee('Przejdź do kursu')
            ->assertDontSee('Zamówienie zostało przyjęte')
            ->assertDontSee('Dokończ płatność');
    }

    public function test_presale_enrollment_is_locked_until_start_date(): void
    {
        Notification::fake();
        [$product, $price] = $this->createOffer();
        $price->update([
            'access_starts_at' => CarbonImmutable::parse('2026-10-01 08:00:00', 'UTC'),
            'access_note' => 'Materiały publikujemy partiami',
        ]);
        $order = $this->createDeferredOrder($product, $price, 'presale-dash@example.test');
        $this->assertTrue(app(ProductOrderFulfillmentService::class)->fulfillOrder($order->id, 'manual')['success']);

        $user = User::query()->where('email', 'presale-dash@example.test')->firstOrFail();
        $enrollment = OnlineCourseEnrollment::query()
            ->where('email', 'presale-dash@example.test')
            ->firstOrFail();

        $this->actingAs($user)
            ->get(route('dashboard.online-courses.index'))
            ->assertOk()
            ->assertSee('Dostęp od 01.10.2026')
            ->assertSee('Materiały publikujemy partiami')
            ->assertDontSee('Przejdź do kursu');

        $this->actingAs($user)
            ->get(route('dashboard.online-courses.show', $enrollment))
            ->assertForbidden();
    }

    public function test_participant_can_resign_only_from_unpaid_online_order(): void
    {
        [$product, $price] = $this->createOffer();
        $unpaid = $this->createDeferredOrder($product, $price, 'rezygnacja@example.test');
        $this->markOrderAwaitingOnlinePayment($unpaid, 'OPO-RESIGN-1');

        $deferred = $this->createDeferredOrder($product, $price, 'rezygnacja@example.test', 'inne-biuro@example.test');
        $user = User::factory()->create(['email' => 'rezygnacja@example.test']);

        $this->actingAs($user)
            ->post(route('dashboard.online-courses.pending.resign', $deferred->ident))
            ->assertRedirect(route('dashboard.online-courses.index'));
        $this->assertNull($deferred->fresh()->cancelled_at);

        $this->actingAs($user)
            ->post(route('dashboard.online-courses.pending.resign', $unpaid->ident))
            ->assertRedirect(route('dashboard.online-courses.index'))
            ->assertSessionHas('success');

        $unpaid->refresh();
        $this->assertNotNull($unpaid->cancelled_at);
        $this->assertSame(FormOrder::PAYMENT_STATUS_CANCELLED, $unpaid->payment_status);
        $this->assertSame(
            OrderItemRecipient::STATUS_CANCELLED,
            $unpaid->orderItems->first()->recipients->first()->status
        );

        $this->actingAs($user)
            ->get(route('dashboard.online-courses.index'))
            ->assertOk()
            ->assertDontSee('Dokończ płatność')
            ->assertSee('Zamówienie zostało przyjęte');
    }

    public function test_stranger_cannot_resign_unpaid_order(): void
    {
        [$product, $price] = $this->createOffer();
        $order = $this->createDeferredOrder($product, $price, 'wlasciciel@example.test');
        $this->markOrderAwaitingOnlinePayment($order, 'OPO-STRANGER-1');
        $stranger = User::factory()->create(['email' => 'obcy@example.test']);

        $this->actingAs($stranger)
            ->post(route('dashboard.online-courses.pending.resign', $order->ident))
            ->assertRedirect(route('dashboard.online-courses.index'))
            ->assertSessionHas('error');

        $this->assertNull($order->fresh()->cancelled_at);
    }

    public function test_resign_removes_only_that_participant_card(): void
    {
        [$product, $price] = $this->createOffer();
        $order = $this->createDeferredOrder(
            $product,
            $price,
            'osoba-a@example.test',
            'biuro-dwoje@example.test',
            [
                ['first_name' => 'Bartek', 'last_name' => 'Beta', 'email' => 'osoba-b@example.test'],
            ]
        );
        $this->markOrderAwaitingOnlinePayment($order, 'OPO-TWO-1');

        $personA = User::factory()->create(['email' => 'osoba-a@example.test']);
        $personB = User::factory()->create(['email' => 'osoba-b@example.test']);

        $this->actingAs($personA)
            ->post(route('dashboard.online-courses.pending.resign', $order->ident))
            ->assertRedirect(route('dashboard.online-courses.index'))
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertNull($order->cancelled_at);
        $this->assertSame(FormOrder::PAYMENT_STATUS_AWAITING_PAYMENT, $order->payment_status);

        $statuses = $order->orderItems->first()->recipients
            ->mapWithKeys(fn (OrderItemRecipient $recipient) => [
                $recipient->email => $recipient->status,
            ]);
        $this->assertSame(OrderItemRecipient::STATUS_CANCELLED, $statuses['osoba-a@example.test']);
        $this->assertSame(OrderItemRecipient::STATUS_PENDING, $statuses['osoba-b@example.test']);

        $this->actingAs($personA)
            ->get(route('dashboard.online-courses.index'))
            ->assertOk()
            ->assertDontSee('Dokończ płatność');

        $this->actingAs($personB)
            ->get(route('dashboard.online-courses.index'))
            ->assertOk()
            ->assertSee('Dokończ płatność')
            ->assertSee('Anuluję / rezygnuję z zamówienia');
    }

    /**
     * @return array{0: Product, 1: ProductPrice}
     */
    private function createOffer(): array
    {
        $course = OnlineCourse::query()->create([
            'slug' => 'kurs-dashboard-'.uniqid(),
            'title' => 'Kurs dashboard oczekujący',
            'description' => 'Opis kursu do testu dashboardu.',
            'is_active' => true,
            'visible_in_dashboard' => true,
        ]);
        $product = Product::query()->create([
            'type' => Product::TYPE_ONLINE_COURSE,
            'resource_id' => $course->id,
            'name' => 'Kurs dashboard oczekujący',
            'slug' => 'kurs-dashboard-'.uniqid(),
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
     * @param  list<array{first_name: string, last_name: string, email: string}>  $extraParticipants
     */
    private function createDeferredOrder(
        Product $product,
        ProductPrice $price,
        string $participantEmail,
        string $ordererEmail = 'biuro-dashboard@example.test',
        array $extraParticipants = []
    ): FormOrder {
        $this->post(route('online-courses.checkout.store', $product->slug), [
            'product_price_id' => $price->id,
            'customer_profile' => 'school',
            'contact_name' => 'Szkoła Dashboard',
            'contact_email' => $ordererEmail,
            'contact_phone' => '501002003',
            'participants' => [
                ['first_name' => 'Anna', 'last_name' => 'Dashboard', 'email' => $participantEmail],
                ...$extraParticipants,
            ],
            'buyer_name' => 'Gmina Testowa',
            'buyer_nip' => '1234567890',
            'buyer_address' => 'ul. Testowa 1',
            'buyer_postcode' => '00-001',
            'buyer_city' => 'Warszawa',
            'payment_type' => 'deferred',
            'payment_terms' => 14,
        ])->assertRedirect();

        return FormOrder::query()
            ->where('orderer_email', $ordererEmail)
            ->latest('id')
            ->firstOrFail();
    }

    private function markOrderAwaitingOnlinePayment(FormOrder $order, string $ident = 'OPO-DASH-1'): void
    {
        $order->update([
            'payment_mode' => FormOrder::PAYMENT_MODE_ONLINE_GATEWAY,
            'payment_status' => FormOrder::PAYMENT_STATUS_AWAITING_PAYMENT,
        ]);

        OnlinePaymentOrder::query()->create([
            'form_order_id' => $order->id,
            'ident' => $ident,
            'course_id' => null,
            'payment_gateway' => 'payu',
            'status' => OnlinePaymentOrder::STATUS_CANCELLED,
            'total_amount' => $order->product_price,
            'currency' => 'PLN',
            'buyer_type' => 'organisation',
            'email' => $order->orderer_email,
            'first_name' => 'Szkoła',
            'last_name' => 'Dashboard',
            'phone' => '501002003',
        ]);
    }
}
