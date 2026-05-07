<?php

namespace App\Http\Controllers;

use App\Models\PneadmCourseSurveyLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExternalSurveyGateController extends Controller
{
    /**
     * Bramka ankiet dla uczestników — pnedu.pl (bez adresu panelu administratora).
     */
    public function visit(string $token): RedirectResponse|View
    {
        $normalized = strtolower(trim($token));
        $link = PneadmCourseSurveyLink::query()
            ->where('public_token', $normalized)
            ->first();

        if (! $link) {
            abort(404);
        }

        $destination = trim((string) ($link->url ?? ''));
        if ($destination === '' || ! filter_var($destination, FILTER_VALIDATE_URL)) {
            abort(503, 'Konfiguracja ankiety jest niekompletna.');
        }

        if ($link->isAvailableNow()) {
            return redirect()->away($destination);
        }

        return view('survey-gate-unavailable', [
            'surveyTitle' => $link->title,
            'opensAt' => $link->opens_at,
            'closesAt' => $link->closes_at,
            'active' => $link->is_active,
        ]);
    }
}
