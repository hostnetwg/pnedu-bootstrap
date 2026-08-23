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

        $this->assertStringContainsString('Materiały szkoleniowe są już dostępne', $text);
        $this->assertStringContainsString('Nagranie i zaświadczenie są już dostępne', $text);
        $this->assertStringNotContainsString('pojawią się wkrótce', $text);
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
