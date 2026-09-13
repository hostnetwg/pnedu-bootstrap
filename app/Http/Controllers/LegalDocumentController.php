<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\Response;

final class LegalDocumentController extends Controller
{
    public function terms(?string $version = null): View
    {
        $termsVersion = $version ?: (string) config('legal.terms.current_version');
        $definition = config("legal.terms.versions.{$termsVersion}");
        abort_unless(is_array($definition) && ($definition['published'] ?? true), 404);

        return view('regulamin', compact('termsVersion'));
    }

    public function termsPdf(string $version): Response
    {
        $definition = config("legal.terms.versions.{$version}");
        abort_unless(is_array($definition) && ($definition['published'] ?? true), 404);

        return Pdf::loadView('legal.terms.pdf', [
            'version' => $version,
            'effectiveAt' => $definition['effective_at'],
            'viewName' => $definition['view'],
        ])->download("regulamin-pnedu-{$version}.pdf");
    }

    public function withdrawal(): View
    {
        return view('legal.withdrawal');
    }

    public function withdrawalPdf(): Response
    {
        return Pdf::loadView('legal.withdrawal-pdf')
            ->download('wzor-odstapienia-od-umowy-pnedu.pdf');
    }
}
