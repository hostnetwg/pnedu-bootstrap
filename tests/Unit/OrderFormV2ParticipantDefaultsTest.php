<?php

namespace Tests\Unit;

use App\Support\OrderFormV2ParticipantDefaults;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrderFormV2ParticipantDefaultsTest extends TestCase
{
    #[DataProvider('profileDefaultProvider')]
    public function test_participant_same_as_contact_default_by_profile(string $profile, bool $expected): void
    {
        $this->assertSame(
            $expected,
            OrderFormV2ParticipantDefaults::isParticipantSameAsContactDefault($profile)
        );
    }

    public static function profileDefaultProvider(): array
    {
        return [
            'school' => ['school', false],
            'organisation' => ['organisation', false],
            'person' => ['person', true],
        ];
    }
}
