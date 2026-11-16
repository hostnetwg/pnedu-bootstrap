<?php

namespace App\Http\Controllers;

use App\Services\CertificateApiClient;
use App\Services\ClosedTrainingSchoolCertificatePage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class ClosedTrainingSchoolCertificateController extends Controller
{
    public function show(string $token, ClosedTrainingSchoolCertificatePage $page): View
    {
        $data = $page->find($token);
        abort_if($data === null, 404);

        return view('closed-training-certificates.show', $data);
    }

    public function revoke(string $token, ClosedTrainingSchoolCertificatePage $page): View
    {
        abort_if(! $page->revoke($token), 404);

        return view('closed-training-certificates.revoked');
    }

    public function download(
        string $token,
        int $participant,
        ClosedTrainingSchoolCertificatePage $page,
        CertificateApiClient $apiClient,
    ): Response|RedirectResponse {
        if (! $page->participantBelongsToToken($token, $participant)) {
            abort(404);
        }

        try {
            $ensured = $apiClient->ensureCertificate($participant, 'pneadm');
            $number = trim((string) ($ensured['certificate_number'] ?? ''));
            if ($number === '') {
                return $this->failed($token);
            }

            $pdf = $apiClient->generatePdf($participant, [
                'connection' => 'pneadm',
                'save_to_storage' => true,
                'cache' => false,
            ]);
        } catch (Throwable) {
            return $this->failed($token);
        }

        $fileName = 'zaswiadczenie-'.Str::of($number)->replace('/', '-')->toString().'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    private function failed(string $token): RedirectResponse
    {
        return redirect()
            ->route('closed-training-certificates.show', ['token' => $token])
            ->with('error', 'Nie udało się przygotować pliku zaświadczenia. Spróbuj ponownie za chwilę.');
    }
}
