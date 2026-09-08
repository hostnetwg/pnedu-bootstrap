<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

final class LegalDocumentService
{
    /**
     * @return array{version: string, effective_at: string, view: string}
     */
    public function termsDefinition(?string $version = null): array
    {
        $resolvedVersion = $version ?: (string) config('legal.terms.current_version');
        $definition = config("legal.terms.versions.{$resolvedVersion}");

        if (! is_array($definition)) {
            throw new \InvalidArgumentException("Nieznana wersja Regulaminu: {$resolvedVersion}");
        }

        return [
            'version' => $resolvedVersion,
            'effective_at' => (string) $definition['effective_at'],
            'view' => (string) $definition['view'],
        ];
    }

    public function termsPdf(?string $version = null): string
    {
        $definition = $this->termsDefinition($version);

        return Pdf::loadView('legal.terms.pdf', [
            'version' => $definition['version'],
            'effectiveAt' => $definition['effective_at'],
            'viewName' => $definition['view'],
        ])->output();
    }

    public function withdrawalFormPdf(): string
    {
        return Pdf::loadView('legal.withdrawal-pdf')->output();
    }
}
