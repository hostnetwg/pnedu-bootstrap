<?php

namespace Tests\Feature;

use App\Services\GusBirService;
use Mockery;
use Tests\TestCase;

class GusLookupTest extends TestCase
{
    public function test_gus_lookup_returns_company_data_for_valid_nip(): void
    {
        $gusBir = Mockery::mock(GusBirService::class);
        $gusBir->shouldReceive('normalizeNip')
            ->once()
            ->with('123-456-78-90')
            ->andReturn('1234567890');
        $gusBir->shouldReceive('lookupByNip')
            ->once()
            ->with('1234567890')
            ->andReturn([
                'nip' => '1234567890',
                'regon' => '123456789',
                'name' => 'Testowa Szkoła',
                'postcode' => '00-001',
                'city' => 'Warszawa',
                'address' => 'Testowa 1',
            ]);

        $this->app->instance(GusBirService::class, $gusBir);

        $response = $this->postJson(route('courses.gus-lookup'), [
            'nip' => '123-456-78-90',
            'target' => 'buyer',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Testowa Szkoła')
            ->assertJsonPath('data.nip', '1234567890');
    }

    public function test_gus_lookup_rejects_invalid_nip(): void
    {
        $gusBir = Mockery::mock(GusBirService::class);
        $gusBir->shouldReceive('normalizeNip')
            ->once()
            ->with('123')
            ->andReturn(null);
        $gusBir->shouldNotReceive('lookupByNip');

        $this->app->instance(GusBirService::class, $gusBir);

        $response = $this->postJson(route('courses.gus-lookup'), [
            'nip' => '123',
            'target' => 'recipient',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('nip');
    }
}
