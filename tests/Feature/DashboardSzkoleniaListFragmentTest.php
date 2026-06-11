<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardSzkoleniaListFragmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_load_szkolenia_list_fragment(): void
    {
        $response = $this->get('/dashboard/fragments/szkolenia-list');

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_load_szkolenia_list_fragment(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard/fragments/szkolenia-list?typ=free');

        $response->assertOk();
        $response->assertSee('js-szkolenia-list-filters', false);
        $response->assertSee('Bezpłatne', false);
    }

    public function test_dashboard_szkolenia_page_includes_ajax_list_root(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard.szkolenia'));

        $response->assertOk();
        $response->assertSee('js-szkolenia-list-root', false);
        $response->assertSee('/dashboard/fragments/szkolenia-list', false);
        $response->assertSee('initSzkoleniaListAjax', false);
    }
}
