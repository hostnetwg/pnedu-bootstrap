<?php

namespace Tests\Unit;

use App\Support\PostTrainingThankYouResources;
use PHPUnit\Framework\TestCase;

class PostTrainingThankYouResourcesTest extends TestCase
{
    public function test_summary_lines_when_all_resources_available(): void
    {
        $resources = new PostTrainingThankYouResources(
            hasMaterials: true,
            hasRecording: true,
            certificateStatus: PostTrainingThankYouResources::CERT_DOWNLOAD_ENABLED,
        );

        $text = implode(' ', array_column($resources->summaryLines(), 'text'));

        $this->assertStringContainsString('Materiały szkoleniowe, nagranie i zaświadczenie są już dostępne na Twoim koncie.', $text);
        $this->assertStringNotContainsString('pojawią się wkrótce', $text);
        $this->assertStringNotContainsString('O gotowości damy znać osobnym e-mailem', $text);
        $this->assertStringContainsString('Możesz od razu skorzystać z zasobów', $text);
        $this->assertEquals(1, substr_count($text, 'na Twoim koncie'));
    }

    public function test_summary_lines_combine_materials_and_certificate_without_repetition(): void
    {
        $resources = new PostTrainingThankYouResources(
            hasMaterials: true,
            hasRecording: false,
            certificateStatus: PostTrainingThankYouResources::CERT_DOWNLOAD_ENABLED,
        );

        $text = implode(' ', array_column($resources->summaryLines(), 'text'));

        $this->assertStringContainsString('Materiały szkoleniowe i zaświadczenie są już dostępne na Twoim koncie.', $text);
        $this->assertEquals(1, substr_count($text, 'na Twoim koncie'));
        $this->assertStringContainsString('Nagranie pojawi się wkrótce', $text);
        $this->assertStringContainsString('O gotowości damy znać osobnym e-mailem', $text);
    }

    public function test_summary_lines_when_nagranie_and_certificate_ready_use_ready_footer(): void
    {
        $resources = new PostTrainingThankYouResources(
            hasMaterials: false,
            hasRecording: true,
            certificateStatus: PostTrainingThankYouResources::CERT_DOWNLOAD_ENABLED,
        );

        $text = implode(' ', array_column($resources->summaryLines(), 'text'));

        $this->assertStringContainsString('Nagranie i zaświadczenie są już dostępne na Twoim koncie.', $text);
        $this->assertStringContainsString('Możesz od razu skorzystać z zasobów', $text);
        $this->assertStringNotContainsString('O gotowości damy znać osobnym e-mailem', $text);
    }

    public function test_summary_lines_when_certificate_is_available_only(): void
    {
        $resources = new PostTrainingThankYouResources(
            hasMaterials: false,
            hasRecording: false,
            certificateStatus: PostTrainingThankYouResources::CERT_DOWNLOAD_ENABLED,
        );

        $text = implode(' ', array_column($resources->summaryLines(), 'text'));

        $this->assertStringContainsString('Zaświadczenie jest już dostępne do pobrania', $text);
        $this->assertStringContainsString('Nagranie pojawi się wkrótce', $text);
    }

    public function test_summary_lines_when_certificate_is_in_preparation(): void
    {
        $resources = new PostTrainingThankYouResources(
            hasMaterials: false,
            hasRecording: false,
            certificateStatus: PostTrainingThankYouResources::CERT_IN_PREPARATION,
        );

        $text = implode(' ', array_column($resources->summaryLines(), 'text'));

        $this->assertStringContainsString('Nagranie i zaświadczenie pojawią się wkrótce', $text);
    }

    public function test_has_no_certificate_hides_list_item(): void
    {
        $resources = new PostTrainingThankYouResources(
            hasMaterials: false,
            hasRecording: false,
            certificateStatus: PostTrainingThankYouResources::CERT_NONE,
        );

        $this->assertTrue($resources->hasNoCertificate());
        $this->assertFalse($resources->showCertificateInList());
    }
}
