<?php

namespace Tests\Feature;

use App\Models\OnlineCourse;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\ProductPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StorefrontCatalogOrderTest extends TestCase
{
    use RefreshDatabase;

    protected $connectionsToTransact = ['mysql', 'pneadm'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        foreach (['products', 'product_offers', 'product_prices', 'online_courses'] as $table) {
            if (! Schema::connection('pneadm')->hasTable($table)) {
                $this->markTestSkipped("Brak tabeli {$table} w bazie pneadm.");
            }
        }

        if (! Schema::connection('pneadm')->hasColumn('online_courses', 'catalog_sort_order')) {
            $this->markTestSkipped('Brak kolumny catalog_sort_order w online_courses.');
        }
    }

    public function test_catalog_lists_open_sales_before_archived_and_uses_adm_order(): void
    {
        $this->createCatalogProduct('Zebra archiwum', 0, salesOpen: false);
        $laterOpen = $this->createCatalogProduct('Późniejszy aktywny', 20, salesOpen: true);
        $earlierOpen = $this->createCatalogProduct('Wcześniejszy aktywny', 10, salesOpen: true);
        $this->createCatalogProduct('Alfa archiwum', 5, salesOpen: false);

        $expected = [
            $earlierOpen->name,
            $laterOpen->name,
            'Zebra archiwum',
            'Alfa archiwum',
        ];

        $orderedNames = Product::query()
            ->catalogOnlineCourses()
            ->orderForPublicCatalog()
            ->pluck('name')
            ->filter(fn (string $name) => in_array($name, $expected, true))
            ->values()
            ->all();

        $this->assertSame($expected, $orderedNames);

        $this->get(route('online-courses.catalog.index'))
            ->assertOk()
            ->assertSeeInOrder($expected);
    }

    private function createCatalogProduct(string $name, int $sortOrder, bool $salesOpen): Product
    {
        $suffix = uniqid();
        $course = OnlineCourse::query()->create([
            'slug' => 'kurs-kolejnosc-'.$suffix,
            'title' => $name,
            'description' => 'Opis katalogu.',
            'is_active' => true,
            'visible_in_dashboard' => true,
            'catalog_sort_order' => $sortOrder,
        ]);
        $product = Product::query()->create([
            'type' => Product::TYPE_ONLINE_COURSE,
            'resource_id' => $course->id,
            'name' => $name,
            'slug' => 'kurs-kolejnosc-'.$suffix,
            'fulfillment_type' => Product::FULFILLMENT_ONLINE_COURSE_ACCESS,
            'is_active' => true,
            'requires_shipping' => false,
        ]);
        $offer = ProductOffer::query()->forceCreate([
            'product_id' => $product->id,
            'code' => ProductOffer::DEFAULT_CODE,
            'sales_channel' => ProductOffer::CHANNEL_PNEDU,
            'is_active' => $salesOpen,
            'is_public' => true,
            'allow_multiple_recipients' => true,
            'allow_deferred_invoice' => true,
            'allow_payu' => true,
            'allow_paynow' => true,
            'satisfaction_guarantee_days' => 30,
            'sort_order' => 10,
        ]);
        ProductPrice::query()->forceCreate([
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

        return $product;
    }
}
