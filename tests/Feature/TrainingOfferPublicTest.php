<?php

namespace Tests\Feature;

use App\Mail\TrainingOfferInquiryMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class TrainingOfferPublicTest extends TestCase
{
    private ?string $publicSlug = null;

    private ?string $hiddenSlug = null;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            if (! Schema::connection('pneadm')->hasTable('training_offers')) {
                $this->markTestSkipped('Brak tabeli training_offers w bazie pneadm.');
            }
        } catch (\Throwable) {
            $this->markTestSkipped('Brak połączenia z bazą pneadm.');
        }
    }

    protected function tearDown(): void
    {
        foreach (array_filter([$this->publicSlug, $this->hiddenSlug]) as $slug) {
            DB::connection('pneadm')
                ->table('training_offers')
                ->where('slug', $slug)
                ->delete();
        }

        parent::tearDown();
    }

    public function test_public_training_offer_is_visible_on_list_and_detail_page(): void
    {
        $this->publicSlug = 'test-publiczna-oferta-'.Str::lower(Str::random(8));
        $this->hiddenSlug = 'test-ukryta-oferta-'.Str::lower(Str::random(8));

        DB::connection('pneadm')->table('training_offers')->insert([
            [
                'title' => 'Publiczna oferta rady pedagogicznej',
                'slug' => $this->publicSlug,
                'summary' => 'Oferta widoczna w katalogu.',
                'description_html' => '<p>Treść pełnego opisu szkolenia.</p>',
                'price_mode' => 'individual',
                'default_course_category' => 'closed',
                'is_active' => true,
                'show_on_pnedu' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Ukryta oferta rady pedagogicznej',
                'slug' => $this->hiddenSlug,
                'summary' => 'Oferta ukryta w katalogu.',
                'description_html' => null,
                'price_mode' => 'individual',
                'default_course_category' => 'closed',
                'is_active' => true,
                'show_on_pnedu' => false,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->get(route('training-offers.pedagogical-councils.index'))
            ->assertOk()
            ->assertSee('Publiczna oferta rady pedagogicznej')
            ->assertDontSee('Ukryta oferta rady pedagogicznej');

        $this->get(route('training-offers.pedagogical-councils.show', $this->publicSlug))
            ->assertOk()
            ->assertSee('Publiczna oferta rady pedagogicznej')
            ->assertSee('Treść pełnego opisu szkolenia.')
            ->assertSee('Cena ustalana indywidualnie');

        $this->get(route('training-offers.pedagogical-councils.show', $this->hiddenSlug))
            ->assertNotFound();
    }

    public function test_training_offer_inquiry_sends_email_and_redirects_back_to_offer(): void
    {
        Mail::fake();

        $this->publicSlug = 'test-zapytanie-oferta-'.Str::lower(Str::random(8));

        DB::connection('pneadm')->table('training_offers')->insert([
            'title' => 'Oferta do zapytania',
            'slug' => $this->publicSlug,
            'summary' => 'Oferta, do której można wysłać zapytanie.',
            'price_mode' => 'individual',
            'default_course_category' => 'closed',
            'is_active' => true,
            'show_on_pnedu' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->post(route('training-offers.pedagogical-councils.inquiry.store', $this->publicSlug), [
            'name' => 'Anna Kowalska',
            'email' => 'anna.kowalska@example.com',
            'phone' => '500 600 700',
            'institution' => 'Szkoła Podstawowa nr 1',
            'preferred_format' => 'onsite',
            'message' => 'Proszę o kontakt w sprawie terminu szkolenia.',
            'consent' => '1',
        ]);

        $response
            ->assertRedirect(route('training-offers.pedagogical-councils.show', $this->publicSlug))
            ->assertSessionHas('success');

        Mail::assertSent(TrainingOfferInquiryMail::class, function (TrainingOfferInquiryMail $mail) {
            return $mail->data['offer_title'] === 'Oferta do zapytania'
                && $mail->data['name'] === 'Anna Kowalska'
                && $mail->data['email'] === 'anna.kowalska@example.com'
                && $mail->hasTo(config('mail.system.reply_to_address'));
        });
    }

    public function test_training_offer_inquiry_validates_required_fields_and_consent(): void
    {
        Mail::fake();

        $this->publicSlug = 'test-walidacja-zapytania-'.Str::lower(Str::random(8));

        DB::connection('pneadm')->table('training_offers')->insert([
            'title' => 'Oferta do walidacji',
            'slug' => $this->publicSlug,
            'summary' => 'Oferta używana w teście walidacji.',
            'price_mode' => 'individual',
            'default_course_category' => 'closed',
            'is_active' => true,
            'show_on_pnedu' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->from(route('training-offers.pedagogical-councils.show', $this->publicSlug))
            ->post(route('training-offers.pedagogical-councils.inquiry.store', $this->publicSlug), [
                'name' => '',
                'email' => 'niepoprawny-email',
                'message' => '',
            ])
            ->assertRedirect(route('training-offers.pedagogical-councils.show', $this->publicSlug))
            ->assertSessionHasErrors(['name', 'email', 'message', 'consent']);

        Mail::assertNothingSent();
    }

    public function test_training_offer_inquiry_cannot_be_sent_for_hidden_offer(): void
    {
        Mail::fake();

        $this->hiddenSlug = 'test-ukryta-zapytanie-'.Str::lower(Str::random(8));

        DB::connection('pneadm')->table('training_offers')->insert([
            'title' => 'Ukryta oferta do zapytania',
            'slug' => $this->hiddenSlug,
            'summary' => 'Ukryta oferta nie powinna przyjmować zapytań.',
            'price_mode' => 'individual',
            'default_course_category' => 'closed',
            'is_active' => true,
            'show_on_pnedu' => false,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post(route('training-offers.pedagogical-councils.inquiry.store', $this->hiddenSlug), [
            'name' => 'Anna Kowalska',
            'email' => 'anna.kowalska@example.com',
            'message' => 'Proszę o kontakt.',
            'consent' => '1',
        ])->assertNotFound();

        Mail::assertNothingSent();
    }

    public function test_homepage_displays_only_featured_training_offers(): void
    {
        $this->publicSlug = 'test-wyrozniona-oferta-'.Str::lower(Str::random(8));
        $this->hiddenSlug = 'test-niewyrozniona-oferta-'.Str::lower(Str::random(8));

        DB::connection('pneadm')->table('training_offers')->insert([
            [
                'title' => 'Wyróżniona oferta rady pedagogicznej',
                'slug' => $this->publicSlug,
                'summary' => 'Oferta widoczna na stronie głównej.',
                'price_mode' => 'individual',
                'default_course_category' => 'closed',
                'is_active' => true,
                'show_on_pnedu' => true,
                'featured_on_homepage' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Niewyróżniona oferta rady pedagogicznej',
                'slug' => $this->hiddenSlug,
                'summary' => 'Oferta nie powinna być na stronie głównej.',
                'price_mode' => 'individual',
                'default_course_category' => 'closed',
                'is_active' => true,
                'show_on_pnedu' => true,
                'featured_on_homepage' => false,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Zamów szkolenie dla rady pedagogicznej')
            ->assertSee('Wyróżniona oferta rady pedagogicznej')
            ->assertDontSee('Niewyróżniona oferta rady pedagogicznej');
    }
}
