<?php

namespace Tests\Feature;

use App\Models\OnlineCourse;
use App\Models\OnlineCourseEnrollment;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\ProductPrice;
use App\Models\User;
use App\Notifications\OnlineCourseProductAccessGranted;
use App\Support\DashboardResourceCounts;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FreeCourseSignupTest extends TestCase
{
    use RefreshDatabase;

    protected $connectionsToTransact = ['mysql', 'pneadm'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Notification::fake();

        foreach (['products', 'product_offers', 'product_prices', 'online_course_enrollments'] as $table) {
            if (! Schema::connection('pneadm')->hasTable($table)) {
                $this->markTestSkipped("Brak tabeli {$table} w bazie pneadm.");
            }
        }

        if (! Schema::connection('pneadm')->hasColumn('product_prices', 'is_complimentary')) {
            $this->markTestSkipped('Brak kolumny is_complimentary w product_prices.');
        }
    }

    public function test_offer_shows_free_cta_without_zero_price_or_payu(): void
    {
        [$product, $paid, $free] = $this->createMixedOffer();

        $html = $this->get(route('online-courses.catalog.show', $product->slug))
            ->assertOk()
            ->assertSee('Zamawiam ten wariant')
            ->assertSee('Zapisz się bezpłatnie')
            ->assertSee('Bezpłatny dostęp')
            ->assertSee('199,00')
            ->assertDontSee('0,00 zł')
            ->getContent();

        $this->assertStringContainsString(
            route('online-courses.checkout.create', ['product' => $product->slug, 'price' => $paid->id]),
            $html
        );
        $this->assertStringContainsString(
            route('online-courses.free-signup.create', ['product' => $product->slug, 'price' => $free->id]),
            $html
        );
        $this->assertStringNotContainsString('PayU', $html);

        $this->get(route('online-courses.catalog.index'))
            ->assertOk()
            ->assertSee('199,00 zł')
            ->assertDontSee('od 199,00')
            ->assertDontSee('/ osoba')
            ->assertDontSee('od 0,00');
    }

    public function test_complimentary_checkout_is_not_available(): void
    {
        [$product, , $free] = $this->createMixedOffer();

        $this->get(route('online-courses.checkout.create', [
            'product' => $product->slug,
            'price' => $free->id,
        ]))->assertNotFound();
    }

    public function test_new_email_gets_account_enrollment_and_mail(): void
    {
        [$product, , $free] = $this->createMixedOffer();

        $this->get(route('online-courses.free-signup.create', [
            'product' => $product->slug,
            'price' => $free->id,
        ]))
            ->assertOk()
            ->assertSee('Zapisz się bezpłatnie')
            ->assertSee('Regulaminu w wersji')
            ->assertDontSee('NIP')
            ->assertDontSee('Potwierdzam zakup');

        $this->post(route('online-courses.free-signup.store', $product->slug), [
            'product_price_id' => $free->id,
            'first_name' => 'Ewa',
            'last_name' => 'Darmowa',
            'email' => 'ewa.darmowa@example.test',
        ])
            ->assertRedirect(route('online-courses.catalog.show', $product->slug))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'ewa.darmowa@example.test',
            'first_name' => 'Ewa',
            'last_name' => 'Darmowa',
        ]);
        $this->assertDatabaseHas('online_course_enrollments', [
            'online_course_id' => $product->resource_id,
            'email' => 'ewa.darmowa@example.test',
            'access_source' => 'free_signup',
        ], 'pneadm');

        $user = User::query()->where('email', 'ewa.darmowa@example.test')->firstOrFail();
        Notification::assertSentTo($user, OnlineCourseProductAccessGranted::class);
    }

    public function test_repeat_signup_does_not_duplicate_enrollment_or_mail(): void
    {
        [$product, , $free] = $this->createMixedOffer();
        $this->post(route('online-courses.free-signup.store', $product->slug), [
            'product_price_id' => $free->id,
            'first_name' => 'Ewa',
            'last_name' => 'Darmowa',
            'email' => 'ewa.powtorka@example.test',
        ])->assertRedirect();

        Notification::fake();

        $this->post(route('online-courses.free-signup.store', $product->slug), [
            'product_price_id' => $free->id,
            'first_name' => 'Ewa',
            'last_name' => 'Inna',
            'email' => 'ewa.powtorka@example.test',
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(
            1,
            OnlineCourseEnrollment::query()
                ->where('online_course_id', $product->resource_id)
                ->where('email', 'ewa.powtorka@example.test')
                ->count()
        );
        Notification::assertNothingSent();
    }

    public function test_dashboard_count_updates_after_logged_in_free_signup(): void
    {
        [$product, , $free] = $this->createMixedOffer();
        $user = User::factory()->create([
            'email' => 'licznik.kursu@example.test',
            'first_name' => 'Ala',
            'last_name' => 'Licznik',
        ]);

        $this->actingAs($user);
        $this->assertSame(0, DashboardResourceCounts::forUser($user)['online_courses']);

        $this->post(route('online-courses.free-signup.store', $product->slug), [
            'product_price_id' => $free->id,
            'first_name' => 'Ala',
            'last_name' => 'Licznik',
            'email' => $user->email,
        ])->assertRedirect();

        $this->assertSame(1, DashboardResourceCounts::forUser($user)['online_courses']);
        $this->get(route('dashboard.online-courses.index'))
            ->assertOk()
            ->assertSee('Kurs darmowy testowy')
            ->assertSee('Kursy online (1)');
    }

    public function test_presale_free_signup_keeps_scheduled_start(): void
    {
        [$product, , $free] = $this->createMixedOffer();
        $free->forceFill([
            'access_starts_at' => CarbonImmutable::parse('2026-12-01 08:00:00', 'UTC'),
        ])->save();

        $this->post(route('online-courses.free-signup.store', $product->slug), [
            'product_price_id' => $free->id,
            'first_name' => 'Piotr',
            'last_name' => 'Start',
            'email' => 'piotr.start@example.test',
        ])->assertRedirect();

        $enrollment = OnlineCourseEnrollment::query()
            ->where('email', 'piotr.start@example.test')
            ->firstOrFail();
        $this->assertNotNull($enrollment->access_starts_at);
        $this->assertSame(
            '2026-12-01',
            $enrollment->access_starts_at->timezone('Europe/Warsaw')->format('Y-m-d')
        );
        $this->assertFalse($enrollment->hasAccessStarted());
    }

    /**
     * @return array{0: Product, 1: ProductPrice, 2: ProductPrice}
     */
    private function createMixedOffer(): array
    {
        $course = OnlineCourse::query()->create([
            'slug' => 'kurs-darmowy-test-'.uniqid(),
            'title' => 'Kurs darmowy testowy',
            'description' => 'Opis.',
            'is_active' => true,
            'visible_in_dashboard' => true,
        ]);
        $product = Product::query()->create([
            'type' => Product::TYPE_ONLINE_COURSE,
            'resource_id' => $course->id,
            'name' => 'Kurs darmowy testowy',
            'slug' => 'kurs-darmowy-test-'.uniqid(),
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
        $paid = ProductPrice::query()->forceCreate([
            'product_offer_id' => $offer->id,
            'name' => 'Dostęp na rok',
            'is_active' => true,
            'is_complimentary' => false,
            'sort_order' => 10,
            'price' => '199.00',
            'currency' => 'PLN',
            'tax_treatment' => ProductPrice::TAX_EXEMPT,
            'is_promotion' => false,
            'access_policy' => ProductPrice::ACCESS_DURATION_FROM_GRANT,
            'access_duration_value' => 1,
            'access_duration_unit' => 'years',
        ]);
        $free = ProductPrice::query()->forceCreate([
            'product_offer_id' => $offer->id,
            'name' => 'Wgląd bezpłatny',
            'is_active' => true,
            'is_complimentary' => true,
            'sort_order' => 20,
            'price' => '0.00',
            'currency' => 'PLN',
            'tax_treatment' => ProductPrice::TAX_EXEMPT,
            'is_promotion' => false,
            'access_policy' => ProductPrice::ACCESS_DURATION_FROM_GRANT,
            'access_duration_value' => 3,
            'access_duration_unit' => 'months',
        ]);

        return [$product, $paid, $free];
    }
}
