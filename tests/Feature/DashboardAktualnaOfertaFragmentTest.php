<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAktualnaOfertaFragmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_load_aktualna_oferta_fragment(): void
    {
        $response = $this->get(route('dashboard.fragments.aktualna-oferta'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_load_aktualna_oferta_fragment(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard.fragments.aktualna-oferta'));

        $response->assertOk();
        $response->assertSee('Aktualna oferta', false);
        $response->assertSee('Zapisz się na szkolenie', false);
    }

    public function test_dashboard_szkolenia_page_uses_lazy_offer_mount(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard.szkolenia'));

        $response->assertOk();
        $response->assertSee('js-dashboard-offer-sidebar', false);
        $response->assertSee('/dashboard/fragments/aktualna-oferta', false);
        $response->assertSee('Ładowanie terminów', false);
        $response->assertSee('initDashboardOfferSidebars', false);
    }

    public function test_dashboard_zaswiadczenia_page_uses_lazy_offer_mount(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard.zaswiadczenia'));

        $response->assertOk();
        $response->assertSee('js-dashboard-offer-sidebar', false);
        $response->assertSee('initDashboardOfferSidebars', false);
    }
}
