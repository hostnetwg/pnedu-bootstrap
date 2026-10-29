<?php

namespace Tests\Feature;

use App\Mail\TrainingOfferInquiryMail;
use App\Models\ClosedTrainingCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class TrainingOfferPublicTest extends TestCase
{
    private ?string $publicSlug = null;

    private ?string $hiddenSlug = null;

    /** @var list<string> */
    private array $extraSlugs = [];

    /** @var list<int> */
    private array $caseIds = [];

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
        foreach (array_filter([$this->publicSlug, $this->hiddenSlug, ...$this->extraSlugs]) as $slug) {
            DB::connection('pneadm')
                ->table('training_offers')
                ->where('slug', $slug)
                ->delete();
        }

        if (Schema::connection('pneadm')->hasTable('closed_training_cases') && $this->caseIds !== []) {
            DB::connection('pneadm')->table('closed_training_case_events')->whereIn('closed_training_case_id', $this->caseIds)->delete();
            DB::connection('pneadm')->table('closed_training_cases')->whereIn('id', $this->caseIds)->delete();
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
            'institution_type' => ClosedTrainingCase::INSTITUTION_PRIMARY,
            'institution_type_other' => 'Ten tekst nie może zostać zapisany',
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
                && $mail->data['institution_type_label'] === 'Szkoła podstawowa'
                && $mail->hasTo(config('mail.system.reply_to_address'));
        });

        $case = $this->assertInquiryStored(
            source: ClosedTrainingCase::SOURCE_OFFER_FORM,
            email: 'anna.kowalska@example.com',
            topic: 'Oferta do zapytania',
        );
        $this->assertSame(ClosedTrainingCase::INSTITUTION_PRIMARY, $case->institution_type);
        $this->assertNull($case->institution_type_other);
        $this->assertSame('Oferta do zapytania', $case->submitted_payload['offer_title']);
        $this->assertSame('anna.kowalska@example.com', $case->submitted_payload['contact_email']);
        $this->assertNull($case->submitted_payload['institution_type_other']);
    }

    public function test_general_inquiry_is_stored_without_an_offer_and_sends_email(): void
    {
        Mail::fake();

        if (! Schema::connection('pneadm')->hasTable('closed_training_cases')) {
            $this->markTestSkipped('Brak tabeli closed_training_cases w bazie pneadm.');
        }

        $email = 'dyrektor.'.Str::lower(Str::random(8)).'@example.com';

        $this->post(route('training-offers.pedagogical-councils.inquiry.general.store'), [
            'name' => 'Jan Testowy',
            'email' => $email,
            'phone' => '600 700 800',
            'institution' => 'Przykładowa szkoła',
            'institution_type' => ClosedTrainingCase::INSTITUTION_PRIMARY,
            'offer_topic' => 'Stres w pracy nauczyciela',
            'preferred_format' => 'online',
            'message' => 'Proszę o propozycję terminu.',
            'consent' => '1',
        ])->assertRedirect(route('training-offers.pedagogical-councils.inquiry.general'))
            ->assertSessionHas('success');

        Mail::assertSent(TrainingOfferInquiryMail::class, function (TrainingOfferInquiryMail $mail) use ($email) {
            return $mail->data['offer_title'] === 'Stres w pracy nauczyciela'
                && $mail->data['offer_url'] === null
                && $mail->data['email'] === $email;
        });

        $case = $this->assertInquiryStored(
            source: ClosedTrainingCase::SOURCE_GENERAL_FORM,
            email: $email,
            topic: 'Stres w pracy nauczyciela',
        );
        $this->assertNull($case->training_offer_id);
        $this->assertSame(ClosedTrainingCase::INSTITUTION_PRIMARY, $case->institution_type);
    }

    public function test_general_inquiry_requires_institution_type_and_keeps_other_detail_only_for_other(): void
    {
        Mail::fake();

        if (! Schema::connection('pneadm')->hasTable('closed_training_cases')) {
            $this->markTestSkipped('Brak tabeli closed_training_cases w bazie pneadm.');
        }

        $payload = [
            'name' => 'Jan Testowy',
            'email' => 'rodzaj.'.Str::lower(Str::random(8)).'@example.com',
            'phone' => '600 700 800',
            'message' => 'Proszę o kontakt.',
            'consent' => '1',
        ];

        $this->post(route('training-offers.pedagogical-councils.inquiry.general.store'), $payload)
            ->assertSessionHasErrors(['institution_type']);

        $this->post(route('training-offers.pedagogical-councils.inquiry.general.store'), [
            ...$payload,
            'institution_type' => ClosedTrainingCase::INSTITUTION_OTHER,
        ])->assertSessionHasErrors(['institution_type_other']);

        $this->post(route('training-offers.pedagogical-councils.inquiry.general.store'), [
            ...$payload,
            'institution_type' => ClosedTrainingCase::INSTITUTION_OTHER,
            'institution_type_other' => 'Poradnia psychologiczno-pedagogiczna',
        ])->assertRedirect(route('training-offers.pedagogical-councils.inquiry.general'))
            ->assertSessionHas('success');

        $case = $this->assertInquiryStored(
            source: ClosedTrainingCase::SOURCE_GENERAL_FORM,
            email: $payload['email'],
            topic: '(temat nie określony)',
        );
        $this->assertSame(ClosedTrainingCase::INSTITUTION_OTHER, $case->institution_type);
        $this->assertSame('Poradnia psychologiczno-pedagogiczna', $case->institution_type_other);
        $this->assertNull($case->submitted_payload['offer_title']);
        $this->assertSame('Poradnia psychologiczno-pedagogiczna', $case->submitted_payload['institution_type_other']);
        $this->assertNull($case->institution);

        Mail::assertSent(TrainingOfferInquiryMail::class, function (TrainingOfferInquiryMail $mail) {
            return $mail->data['institution_type_label'] === 'Inna — Poradnia psychologiczno-pedagogiczna';
        });
    }

    public function test_general_inquiry_keeps_the_case_when_mail_fails(): void
    {
        if (! Schema::connection('pneadm')->hasTable('closed_training_cases')) {
            $this->markTestSkipped('Brak tabeli closed_training_cases w bazie pneadm.');
        }

        $pending = \Mockery::mock();
        $pending->shouldReceive('send')->once()->andThrow(new \RuntimeException('smtp down'));
        Mail::shouldReceive('to')->once()->andReturn($pending);

        $email = 'mail-fail.'.Str::lower(Str::random(8)).'@example.com';

        $this->post(route('training-offers.pedagogical-councils.inquiry.general.store'), [
            'name' => 'Jan Testowy',
            'email' => $email,
            'phone' => '600 700 800',
            'offer_topic' => 'Odpowiedzialność prawna nauczyciela',
            'institution_type' => ClosedTrainingCase::INSTITUTION_PRIMARY,
            'message' => 'Proszę o kontakt w sprawie terminu.',
            'consent' => '1',
        ])->assertRedirect(route('training-offers.pedagogical-councils.inquiry.general'))
            ->assertSessionHas('success');

        $this->assertInquiryStored(
            source: ClosedTrainingCase::SOURCE_GENERAL_FORM,
            email: $email,
            topic: 'Odpowiedzialność prawna nauczyciela',
        );
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
            ->assertSessionHasErrors(['name', 'email', 'phone', 'message', 'consent', 'institution_type']);

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
        $this->extraSlugs[] = 'test-wyrozniona-oferta-extra-'.Str::lower(Str::random(8));
        $extraSlug = $this->extraSlugs[0];

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
                'title' => 'Druga wyróżniona oferta RP',
                'slug' => $extraSlug,
                'summary' => 'Kolejna oferta w karuzeli na stronie głównej.',
                'price_mode' => 'individual',
                'default_course_category' => 'closed',
                'is_active' => true,
                'show_on_pnedu' => true,
                'featured_on_homepage' => true,
                'sort_order' => 1,
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
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Zamów szkolenie dla rady pedagogicznej')
            ->assertSee('Wyróżniona oferta rady pedagogicznej')
            ->assertSee('Druga wyróżniona oferta RP')
            ->assertSee('data-featured-offers-carousel', false)
            ->assertSee('data-load-url="'.route('fragments.featured-training-offers').'"', false)
            ->assertDontSee('Niewyróżniona oferta rady pedagogicznej');
    }

    public function test_featured_training_offers_fragment_loads_next_batch(): void
    {
        $prefix = 'Oferta batch '.Str::upper(Str::random(4));
        $slugs = [];
        $rows = [];
        for ($i = 1; $i <= 7; $i++) {
            $slug = 'test-featured-batch-'.$i.'-'.Str::lower(Str::random(6));
            $slugs[] = $slug;
            $rows[] = [
                'title' => "{$prefix} {$i}",
                'slug' => $slug,
                'summary' => "Podsumowanie batch {$i}",
                'price_mode' => 'individual',
                'default_course_category' => 'closed',
                'is_active' => true,
                'show_on_pnedu' => true,
                'featured_on_homepage' => true,
                'sort_order' => 9000 + $i,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        $this->extraSlugs = $slugs;

        DB::connection('pneadm')->table('training_offers')->insert($rows);

        $initial = \App\Support\FeaturedHomepageTrainingOffers::page(
            0,
            \App\Support\FeaturedHomepageTrainingOffers::INITIAL_LIMIT
        );
        $remainder = \App\Support\FeaturedHomepageTrainingOffers::page(
            \App\Support\FeaturedHomepageTrainingOffers::INITIAL_LIMIT,
            \App\Support\FeaturedHomepageTrainingOffers::BATCH_LIMIT
        );

        $home = $this->get(route('home'))->assertOk();
        $home->assertSee('data-load-url="'.route('fragments.featured-training-offers').'"', false)
            ->assertSee('data-batch-size="'.\App\Support\FeaturedHomepageTrainingOffers::BATCH_LIMIT.'"', false);

        foreach ($initial as $offer) {
            $home->assertSee($offer->title);
        }
        foreach ($remainder->where(fn ($offer) => str_starts_with($offer->title, $prefix)) as $offer) {
            $home->assertDontSee($offer->title);
        }

        $fragment = $this->get(route('fragments.featured-training-offers', [
            'offset' => \App\Support\FeaturedHomepageTrainingOffers::INITIAL_LIMIT,
            'limit' => \App\Support\FeaturedHomepageTrainingOffers::BATCH_LIMIT,
        ]));

        $fragment->assertOk()
            ->assertHeader('X-Featured-Offers-Count', (string) $remainder->count());

        foreach ($remainder as $offer) {
            $fragment->assertSee($offer->title);
        }
        $this->assertGreaterThanOrEqual(7, (int) $fragment->headers->get('X-Featured-Offers-Total'));
    }

    private function assertInquiryStored(string $source, string $email, string $topic): ClosedTrainingCase
    {
        if (! Schema::connection('pneadm')->hasTable('closed_training_cases')) {
            $this->fail('Brak tabeli closed_training_cases. Uruchom migrację w pneadm.');
        }

        $case = ClosedTrainingCase::query()->where('contact_email', $email)->latest('id')->first();
        $this->assertNotNull($case);
        $this->assertSame($source, $case->source);
        $this->assertSame(ClosedTrainingCase::STATUS_NEW, $case->status);
        $this->assertSame($topic, $case->topic);
        $this->assertNotNull($case->consent_at);
        $this->assertSame(1, $case->events()->count());
        $this->caseIds[] = $case->id;

        return $case;
    }
}
