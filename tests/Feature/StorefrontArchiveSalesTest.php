<?php

namespace Tests\Feature;

use App\Models\OnlineCourse;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\ProductPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StorefrontArchiveSalesTest extends TestCase
{
    use RefreshDatabase;

    protected $connectionsToTransact = ['mysql', 'pneadm'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        foreach (['products', 'product_offers', 'product_prices'] as $table) {
            if (! Schema::connection('pneadm')->hasTable($table)) {
                $this->markTestSkipped("Brak tabeli {$table} w bazie pneadm.");
            }
        }
    }

    public function test_catalog_shows_archived_course_without_purchase(): void
    {
        [$product] = $this->createArchivedOffer();

        $this->get(route('online-courses.catalog.index'))
            ->assertOk()
            ->assertSee('Kurs archiwalny testowy')
            ->assertSee('Sprzedaż wyłączona')
            ->assertDontSee('199,00 zł')
            ->assertDontSee('od 199,00')
            ->assertDontSee('/ osoba')
            ->assertDontSee('Zamawiam ten wariant');

        $this->get(route('online-courses.catalog.show', $product->slug))
            ->assertOk()
            ->assertSee('Sprzedaż wyłączona')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertDontSee('Zamawiam ten wariant')
            ->assertDontSee('Wybierz dostęp');

        $this->get(route('online-courses.checkout.create', [
            'product' => $product->slug,
            'price' => 1,
        ]))->assertNotFound();
    }

    /**
     * @return array{0: Product, 1: ProductPrice}
     */
    private function createArchivedOffer(): array
    {
        $course = OnlineCourse::query()->create([
            'slug' => 'kurs-archiwalny-testowy-'.uniqid(),
            'title' => 'Kurs archiwalny testowy',
            'description' => 'Stara wersja aplikacji.',
            'is_active' => true,
            'visible_in_dashboard' => true,
        ]);
        $product = Product::query()->create([
            'type' => Product::TYPE_ONLINE_COURSE,
            'resource_id' => $course->id,
            'name' => 'Kurs archiwalny testowy',
            'slug' => 'kurs-archiwalny-testowy-'.uniqid(),
            'fulfillment_type' => Product::FULFILLMENT_ONLINE_COURSE_ACCESS,
            'is_active' => true,
            'requires_shipping' => false,
        ]);
        $offer = ProductOffer::query()->forceCreate([
            'product_id' => $product->id,
            'code' => ProductOffer::DEFAULT_CODE,
            'sales_channel' => ProductOffer::CHANNEL_PNEDU,
            'is_active' => false,
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
}
