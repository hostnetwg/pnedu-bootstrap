<?php

namespace Tests\Unit;

use Tests\TestCase;

class DashboardTransmisjaLiveOfferModalTest extends TestCase
{
    public function test_transmisja_uses_bootstrap_offer_modal_instead_of_gold_bar(): void
    {
        $page = file_get_contents(resource_path('views/dashboard/szkolenia-transmisja.blade.php'));
        $modal = file_get_contents(resource_path('views/dashboard/partials/transmisja-live-offer-modal.blade.php'));

        $this->assertIsString($page);
        $this->assertIsString($modal);
        $this->assertStringContainsString('transmisja-live-offer-modal', $page);
        $this->assertStringNotContainsString('transmisja-live-offer-bar', $page);
        $this->assertStringContainsString('cmLiveOfferModal', $modal);
        $this->assertStringContainsString('modal-dialog-centered', $modal);
        $this->assertStringContainsString('data-live-offer-image', $modal);
        $this->assertStringContainsString('data-live-offer-description', $modal);
        $this->assertStringContainsString('data-live-offer-price-wrap', $modal);
        $this->assertStringContainsString('btn btn-outline-primary', $modal);
        $this->assertStringContainsString('Opis szkolenia', $modal);
        $this->assertMatchesRegularExpression(
            '/Zamknij[\s\S]*Opis szkolenia[\s\S]*Zamawiam szkolenie/',
            $modal
        );
        $this->assertStringContainsString('Zamawiam szkolenie', $modal);
        $this->assertStringContainsString('object-fit: contain', $page);
        $this->assertStringContainsString('pre-wrap', $page);
        $this->assertStringContainsString('data-bs-backdrop="false"', $modal);
        $this->assertStringContainsString('cm-live-offer-modal__dialog', $modal);
        $this->assertStringContainsString('backdrop: false', $page);
        $this->assertStringContainsString('ensureLiveOfferModalInstance', $page);
        $this->assertStringContainsString('pointer-events: none !important', $page);
    }
}
