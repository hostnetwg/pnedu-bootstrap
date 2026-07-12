<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\PaymentDisplayOption;
use App\Models\User;
use App\Support\OrderFormVariant;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderFormV2Test extends TestCase
{
    private ?PaymentDisplayOption $displayOptions = null;

    private bool $originalFlag = false;

    private string $originalVariant = 'legacy';

    private bool $originalShowLegacy = true;

    private bool $originalAutoFillTestData = false;

    private bool $originalAutoFillDevelopersOnly = false;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            if (! Schema::connection('pneadm')->hasTable('courses')
                || ! Schema::connection('pneadm')->hasTable('payment_display_options')
                || ! Schema::connection('pneadm')->hasColumn('payment_display_options', 'show_order_form_v2')) {
                $this->markTestSkipped('Brak tabel lub kolumny show_order_form_v2 w bazie pneadm.');
            }
        } catch (\Throwable) {
            $this->markTestSkipped('Brak połączenia z testową bazą pneadm.');
        }

        $this->displayOptions = PaymentDisplayOption::query()->first();
        if (! $this->displayOptions) {
            $this->markTestSkipped('Brak rekordu payment_display_options.');
        }

        $this->originalFlag = (bool) $this->displayOptions->show_order_form_v2;
        $this->originalVariant = (string) ($this->displayOptions->default_signup_order_form_variant ?? 'legacy');
        $this->originalShowLegacy = (bool) $this->displayOptions->show_order_form;
        $this->originalAutoFillTestData = (bool) $this->displayOptions->order_form_auto_fill_test_data;
        $this->originalAutoFillDevelopersOnly = (bool) $this->displayOptions->order_form_auto_fill_test_data_developers_only;
    }

    protected function tearDown(): void
    {
        if ($this->displayOptions) {
            $this->displayOptions->forceFill([
                'show_order_form_v2' => $this->originalFlag,
                'default_signup_order_form_variant' => $this->originalVariant,
                'show_order_form' => $this->originalShowLegacy,
                'order_form_auto_fill_test_data' => $this->originalAutoFillTestData,
                'order_form_auto_fill_test_data_developers_only' => $this->originalAutoFillDevelopersOnly,
            ])->save();
        }

        parent::tearDown();
    }

    public function test_flag_hides_cta_and_blocks_direct_v2_url(): void
    {
        $course = $this->activeCourse();
        $this->setFlag(false);

        $this->get(route('courses.show', $course->id))
            ->assertOk()
            ->assertDontSee('data-order-form-variant="v2"', false);

        $this->get($this->orderFormUrl($course, 'payment.order-form-v2'))
            ->assertNotFound();
    }

    public function test_flag_shows_cta_and_v2_form_renders(): void
    {
        $course = $this->activeCourse();
        $this->setFlag(true);

        $this->get(route('courses.show', $course->id))
            ->assertOk()
            ->assertSee('data-order-form-variant="v2"', false)
            ->assertSee('btn-purchase-cta-v2', false);

        $this->get($this->orderFormUrl($course, 'payment.order-form-v2'))
            ->assertOk()
            ->assertSee('id="order-form-v2"', false)
            ->assertSee('data-form-variant="v2"', false)
            ->assertSee('data-analytics-section-v2="offer_summary"', false)
            ->assertSee('Zamawiasz szkolenie')
            ->assertSee('<strong>Data:</strong>', false)
            ->assertSee('<strong>Dodatkowo:</strong>', false)
            ->assertSee('Zobacz pełny opis szkolenia')
            ->assertSee('href="'.route('courses.show', $course->id).'"', false)
            ->assertSee('title="Przejdź do opisu szkolenia"', false)
            ->assertSee('Szkoła publiczna / JST')
            ->assertSee('Pola odbiorcy są opcjonalne')
            ->assertSee('name="has_optional_recipient" value="1" checked', false)
            ->assertSee('id="v2-recipient-heading"', false)
            ->assertSee('>Odbiorca<', false)
            ->assertDontSee('Odbiorca — szkoła')
            ->assertDontSee('Nabywca — organ prowadzący')
            ->assertSee('id="contact_name_display"', false)
            ->assertSee('Nazwa / imię nazwisko zamawiającego')
            ->assertSee('id="contact_first_name"', false)
            ->assertSee('id="v2-participant-toggle-wrap"', false)
            ->assertSee('id="participant_is_contact"', false)
            ->assertDontSee('id="participant_is_contact" name="participant_is_contact" value="1" checked', false);

        $priceInfo = $course->getPriceInfoForOrderFormHeader(
            $this->activeVariants($course)->first()?->id
        );
        if ($priceInfo && ! empty($priceInfo['original_price']) && (float) $priceInfo['original_price'] > (float) $priceInfo['price']) {
            $this->get($this->orderFormUrl($course, 'payment.order-form-v2'))
                ->assertSee('Cena promocyjna')
                ->assertSee('zamiast '.number_format($priceInfo['original_price'], 2, ',', ' ').' PLN');
        }
        if (
            $this->activeVariants($course)->count() > 1
            && ! empty($priceInfo['variant_name'])
            && ! preg_match('/^#\d+$/', (string) $priceInfo['variant_name'])
        ) {
            $this->get($this->orderFormUrl($course, 'payment.order-form-v2'))
                ->assertSee('Wariant: '.$priceInfo['variant_name']);
        } elseif ($this->activeVariants($course)->count() <= 1) {
            $this->get($this->orderFormUrl($course, 'payment.order-form-v2'))
                ->assertDontSee('Wariant:');
        }
        if ($course->is_paid) {
            $this->get($this->orderFormUrl($course, 'payment.order-form-v2'))
                ->assertSee('<strong>Dostęp do nagrania:</strong>', false);
        }
    }

    public function test_v2_post_requires_phone(): void
    {
        $course = $this->activeCourse();
        $this->setFlag(true);
        $payload = $this->validPayload($course);
        unset($payload['contact_phone']);

        $this->from($this->orderFormUrl($course, 'payment.order-form-v2'))
            ->post(route('payment.order-form-v2.store', $course->id), $payload)
            ->assertRedirect()
            ->assertSessionHasErrors('contact_phone');
    }

    public function test_v2_post_rejects_payment_terms_above_30(): void
    {
        $course = $this->activeCourse();
        $this->setFlag(true);
        $payload = $this->validPayload($course);
        $payload['payment_terms'] = 31;

        $this->from($this->orderFormUrl($course, 'payment.order-form-v2'))
            ->post(route('payment.order-form-v2.store', $course->id), $payload)
            ->assertRedirect()
            ->assertSessionHasErrors('payment_terms');
    }

    public function test_legacy_order_form_remains_available_when_v2_is_disabled(): void
    {
        $course = $this->activeCourse();
        $this->setFlag(false);

        $this->get($this->orderFormUrl($course, 'payment.order-form'))
            ->assertOk()
            ->assertSee('action="'.route('payment.order-form.store', $course->id).'"', false);
    }

    public function test_gateway_shows_v2_when_default_variant_is_v2(): void
    {
        $course = $this->activeCourse();
        $this->displayOptions->forceFill([
            'show_order_form' => false,
            'show_order_form_v2' => true,
            'default_signup_order_form_variant' => OrderFormVariant::V2,
        ])->save();
        $this->displayOptions->refresh();

        $this->get(route('courses.show', $course->id))
            ->assertOk()
            ->assertSee('btn-purchase-cta-v2', false)
            ->assertDontSee('class="btn btn-purchase-cta btn-lg fw-bold w-100"', false);

        $this->get($this->orderFormUrl($course, 'payment.order-form'))
            ->assertOk()
            ->assertSee('id="order-form-v2"', false)
            ->assertSee('action="'.route('payment.order-form-v2.store', $course->id).'"', false);
    }

    public function test_gateway_query_param_can_force_legacy_when_v2_is_default(): void
    {
        $course = $this->activeCourse();
        $this->displayOptions->forceFill([
            'show_order_form' => true,
            'show_order_form_v2' => true,
            'default_signup_order_form_variant' => OrderFormVariant::V2,
        ])->save();
        $this->displayOptions->refresh();

        $url = $this->orderFormUrl($course, 'payment.order-form');
        $url .= (str_contains($url, '?') ? '&' : '?').'form_variant=legacy';

        $this->get($url)
            ->assertOk()
            ->assertSee('action="'.route('payment.order-form.store', $course->id).'"', false)
            ->assertDontSee('id="order-form-v2"', false);
    }

    public function test_test_mode_does_not_prefill_contact_email_from_logged_in_user(): void
    {
        $course = $this->activeCourse();
        $this->setFlag(true);

        $developerEmail = 'waldemar.grabowski@hostnet.pl';
        $this->displayOptions->forceFill([
            'order_form_auto_fill_test_data' => false,
            'order_form_auto_fill_test_data_developers_only' => true,
        ])->save();
        $this->displayOptions->refresh();

        $user = new User(['email' => $developerEmail]);
        $user->id = 1;

        $response = $this->withoutMiddleware(\App\Http\Middleware\RecordPneduUserLoginSession::class)
            ->actingAs($user)
            ->get($this->orderFormUrl($course, 'payment.order-form-v2'))
            ->assertOk()
            ->assertSee('id="v2-fill-test"', false)
            ->assertSee('Wypełnij dane testowe');

        $html = $response->getContent();
        $this->assertStringContainsString('id="contact_email"', $html);
        $this->assertStringNotContainsString('value="'.$developerEmail.'"', $html);
        $this->assertStringNotContainsString("value='".$developerEmail."'", $html);
    }

    private function setFlag(bool $enabled, string $variant = 'v2'): void
    {
        $payload = ['show_order_form_v2' => $enabled];
        if ($enabled) {
            $payload['default_signup_order_form_variant'] = $variant;
        }

        $this->displayOptions->forceFill($payload)->save();
        $this->displayOptions->refresh();
    }

    private function activeCourse(): Course
    {
        $course = Course::with('priceVariants')
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if (! $course) {
            $this->markTestSkipped('Brak aktywnego kursu.');
        }

        return $course;
    }

    private function activeVariants(Course $course)
    {
        return $course->priceVariants
            ->filter(fn ($variant) => $variant->is_active && $variant->isAvailableForCourseEndState($course->hasEnded()))
            ->sortBy('id')
            ->values();
    }

    private function orderFormUrl(Course $course, string $routeName): string
    {
        $variants = $this->activeVariants($course);
        $parameters = ['id' => $course->id];
        if ($variants->count() > 1) {
            $parameters['price_variant_id'] = $variants->first()->id;
        }

        return route($routeName, $parameters);
    }

    private function validPayload(Course $course): array
    {
        $variant = $this->activeVariants($course)->first();

        return [
            'form_variant' => 'v2',
            'buyer_type' => 'organisation',
            'payment_type' => 'deferred',
            'contact_name' => 'Jan Kowalski',
            'contact_phone' => '500600700',
            'contact_email' => 'order-form-v2-test@example.test',
            'buyer_name' => 'Testowa Jednostka',
            'buyer_nip' => '1234567890',
            'buyer_address' => 'Testowa 1',
            'buyer_postcode' => '00-001',
            'buyer_city' => 'Warszawa',
            'participant_first_name' => 'Jan',
            'participant_last_name' => 'Kowalski',
            'participant_email' => 'participant-v2-test@example.test',
            'payment_terms' => 14,
            'price_variant_id' => $variant?->id,
        ];
    }
}
