<?php

namespace Tests\Unit;

use App\Models\Participant;
use Tests\TestCase;

class DashboardCertificateParticipantNameTest extends TestCase
{
    public function test_partial_renders_participant_full_name(): void
    {
        $html = view('certificates.partials.participant-name', [
            'participant' => new Participant([
                'first_name' => 'Anna',
                'last_name' => 'Kowalska',
            ]),
        ])->render();

        $this->assertStringContainsString('Zaświadczenie dla:', $html);
        $this->assertStringContainsString('Anna Kowalska', $html);
    }

    public function test_partial_omits_line_when_name_is_empty(): void
    {
        $html = view('certificates.partials.participant-name', [
            'participant' => new Participant([
                'first_name' => '',
                'last_name' => '',
            ]),
        ])->render();

        $this->assertStringNotContainsString('Zaświadczenie dla:', $html);
    }

    public function test_certificate_flow_views_include_participant_name_partial(): void
    {
        foreach ([
            'certificates/birth-data-form.blade.php',
            'certificates/preview-and-download.blade.php',
            'certificates/download-with-redirect.blade.php',
        ] as $relativePath) {
            $source = file_get_contents(resource_path('views/'.$relativePath));
            $this->assertIsString($source, $relativePath);
            $this->assertStringContainsString(
                'certificates.partials.participant-name',
                $source,
                $relativePath
            );
        }
    }
}
