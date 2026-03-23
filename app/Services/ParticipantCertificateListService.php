<?php

namespace App\Services;

use App\Models\Participant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ParticipantCertificateListService
{
    /**
     * Lista szkoleń / zaświadczeń dla znormalizowanego e-maila uczestnika (baza pneadm).
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginatedItemsForEmail(string $emailNormalized, int $perPage = 15): LengthAwarePaginator
    {
        $participants = Participant::whereRaw('LOWER(TRIM(email)) = ?', [$emailNormalized])
            ->whereHas('course')
            ->with(['course.instructor', 'certificate'])
            ->orderByDesc('course_id')
            ->paginate($perPage);

        $statusMap = [
            'download_enabled' => 'pobierz',
            'in_preparation' => 'w_przygotowaniu',
            'no_certificate' => 'brak',
        ];

        $participants->setCollection(
            $participants->getCollection()->map(function ($participant) use ($statusMap) {
                $course = $participant->course;
                $certStatus = $course->certificate_download_status ?? 'in_preparation';
                $canDownload = ($certStatus === 'download_enabled');

                return [
                    'participant' => $participant,
                    'course' => $course,
                    'certificate' => $participant->certificate,
                    'status' => $statusMap[$certStatus] ?? 'w_przygotowaniu',
                    'status_key' => $certStatus,
                    'can_download' => $canDownload,
                ];
            })
        );

        return $participants;
    }
}
