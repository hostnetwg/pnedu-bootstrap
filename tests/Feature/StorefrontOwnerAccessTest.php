<?php

namespace Tests\Feature;

use App\Models\OnlineCourse;
use App\Models\OnlineCourseEnrollment;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\ProductPrice;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StorefrontOwnerAccessTest extends TestCase
{
    use RefreshDatabase;

    protected $connectionsToTransact = ['mysql', 'pneadm'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        foreach (['products', 'product_offers', 'product_prices', 'online_course_enrollments'] as $table) {
            if (! Schema::connection('pneadm')->hasTable($table)) {
                $this->markTestSkipped("Brak tabeli {$table} w bazie pneadm.");
            }
        }
    }

    public function test_guest_still_sees_catalog_price_and_order_buttons(): void
    {
        [$product] = $this->createOffer();

        $this->get(route('online-courses.catalog.index'))
            ->assertOk()
            ->assertSee('od 199,00 zł / osoba')
            ->assertSee('Zobacz kurs')
            ->assertDontSee('Przejdź do kursu');

        $this->get(route('online-courses.catalog.show', $product->slug))
            ->assertOk()
            ->assertSee('Wybierz dostęp')
            ->assertSee('Zamawiam ten wariant')
            ->assertDontSee('Przejdź do kursu')
            ->assertDontSee('Przedłuż dostęp');
    }

    public function test_unlimited_access_replaces_prices_with_enter_course(): void
    {
        [$product, , $course] = $this->createOffer();
        $user = User::factory()->create(['email' => 'wlasciciel-bezterminowy@example.test']);
        $this->enroll($course, $user->email, expiresAt: null);

        $this->actingAs($user)
            ->get(route('online-courses.catalog.index'))
            ->assertOk()
            ->assertSee('Masz dostęp bezterminowy')
            ->assertSee('Przejdź do kursu')
            ->assertDontSee('od 199,00 zł / osoba');

        $this->actingAs($user)
            ->get(route('online-courses.catalog.show', $product->slug))
            ->assertOk()
            ->assertSee('Masz dostęp bezterminowy')
            ->assertSee('Przejdź do kursu')
            ->assertDontSee('Wybierz dostęp')
            ->assertDontSee('Zamawiam ten wariant')
            ->assertDontSee('Przedłuż dostęp');
    }

    public function test_timed_access_shows_end_date_and_extend_buttons(): void
    {
        [$product, , $course] = $this->createOffer();
        $user = User::factory()->create(['email' => 'wlasciciel-rok@example.test']);
        $this->enroll($course, $user->email, expiresAt: CarbonImmutable::parse('2027-03-15 12:00:00', 'UTC'));

        $this->actingAs($user)
            ->get(route('online-courses.catalog.index'))
            ->assertOk()
            ->assertSee('Dostęp do')
            ->assertSee('15.03.2027')
            ->assertSee('Przejdź do kursu')
            ->assertSee('Zobacz kurs');

        $this->actingAs($user)
            ->get(route('online-courses.catalog.show', $product->slug))
            ->assertOk()
            ->assertSee('Masz aktywny dostęp do')
            ->assertSee('15.03.2027')
            ->assertSee('Przejdź do kursu')
            ->assertSee('Chcesz dłużej?')
            ->assertSee('Przedłuż dostęp')
            ->assertDontSee('Zamawiam ten wariant');
    }

    public function test_scheduled_access_hides_enter_and_keeps_offer(): void
    {
        [$product, , $course] = $this->createOffer();
        $user = User::factory()->create(['email' => 'wlasciciel-presale@example.test']);
        $this->enroll(
            $course,
            $user->email,
            expiresAt: CarbonImmutable::parse('2027-12-01 12:00:00', 'UTC'),
            startsAt: CarbonImmutable::parse('2026-10-01 08:00:00', 'UTC')
        );

        $this->actingAs($user)
            ->get(route('online-courses.catalog.index'))
            ->assertOk()
            ->assertSee('Dostęp od')
            ->assertSee('01.10.2026')
            ->assertDontSee('Przejdź do kursu');

        $this->actingAs($user)
            ->get(route('online-courses.catalog.show', $product->slug))
            ->assertOk()
            ->assertSee('Dostęp od')
            ->assertSee('Zamawiam ten wariant')
            ->assertDontSee('Przejdź do kursu');
    }

    public function test_expired_access_keeps_regular_purchase(): void
    {
        [$product, , $course] = $this->createOffer();
        $user = User::factory()->create(['email' => 'wlasciciel-wygasl@example.test']);
        $this->enroll($course, $user->email, expiresAt: CarbonImmutable::parse('2026-01-10 12:00:00', 'UTC'));

        $this->actingAs($user)
            ->get(route('online-courses.catalog.index'))
            ->assertOk()
            ->assertSee('Dostęp skończył się 10.01.2026')
            ->assertSee('od 199,00 zł / osoba')
            ->assertSee('Zobacz kurs')
            ->assertDontSee('Przejdź do kursu');

        $this->actingAs($user)
            ->get(route('online-courses.catalog.show', $product->slug))
            ->assertOk()
            ->assertSee('Twój dostęp skończył się')
            ->assertSee('Zamawiam ten wariant')
            ->assertDontSee('Przedłuż dostęp');
    }

    public function test_other_account_does_not_see_owner_state(): void
    {
        [$product, , $course] = $this->createOffer();
        $this->enroll($course, 'uczestnik@example.test', expiresAt: null);
        $stranger = User::factory()->create(['email' => 'kto-inny@example.test']);

        $this->actingAs($stranger)
            ->get(route('online-courses.catalog.show', $product->slug))
            ->assertOk()
            ->assertSee('Zamawiam ten wariant')
            ->assertDontSee('Masz dostęp bezterminowy');
    }

    /**
     * @return array{0: Product, 1: ProductPrice, 2: OnlineCourse}
     */
    private function createOffer(): array
    {
        $course = OnlineCourse::query()->create([
            'slug' => 'kurs-dostep-testowy-'.uniqid(),
            'title' => 'Kurs dostęp testowy',
            'description' => 'Opis do testu stanu dostępu.',
            'is_active' => true,
            'visible_in_dashboard' => true,
        ]);
        $product = Product::query()->create([
            'type' => Product::TYPE_ONLINE_COURSE,
            'resource_id' => $course->id,
            'name' => 'Kurs dostęp testowy',
            'slug' => 'kurs-dostep-testowy-'.uniqid(),
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

        return [$product, $price, $course];
    }

    private function enroll(
        OnlineCourse $course,
        string $email,
        ?CarbonImmutable $expiresAt,
        ?CarbonImmutable $startsAt = null
    ): OnlineCourseEnrollment {
        return OnlineCourseEnrollment::query()->create([
            'online_course_id' => $course->id,
            'email' => $email,
            'first_name' => 'Anna',
            'last_name' => 'Test',
            'access_expires_at' => $expiresAt,
            'access_starts_at' => $startsAt,
            'access_source' => 'test',
        ]);
    }
}
