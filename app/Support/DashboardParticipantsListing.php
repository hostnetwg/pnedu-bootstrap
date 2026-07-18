<?php

namespace App\Support;

use App\Models\CourseFileLink;
use App\Models\Participant;
use App\Models\ParticipantTrainingVideoNote;
use App\Services\DashboardCourseLiveAccessService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardParticipantsListing
{
    /**
     * @return array{participants: LengthAwarePaginator, szkoleniaTyp: string, szkoleniaCounts: array{all: int, paid: int, free: int}}
     */
    public static function forAuthenticatedUser(Request $request): array
    {
        $userEmail = Auth::user()->email;
        $emailNormalized = Participant::normalizeEmail($userEmail) ?? '';

        $typ = $request->query('typ', 'all');
        if (! in_array($typ, ['all', 'paid', 'free'], true)) {
            $typ = 'all';
        }

        $szkoleniaCounts = DashboardResourceCounts::szkoleniaFilterCountsForEmail($emailNormalized);

        $query = Participant::query()
            ->forNormalizedEmail($emailNormalized)
            ->leftJoin('courses', 'participants.course_id', '=', 'courses.id')
            ->select('participants.*')
            ->with([
                'course' => function ($courseQuery) {
                    $courseQuery->select([
                        'id',
                        'title',
                        'start_date',
                        'end_date',
                        'is_paid',
                        'type',
                        'instructor_id',
                        'certificate_download_status',
                    ])->withCount(['videos', 'fileLinks']);
                },
                'course.instructor:id,title,first_name,last_name,gender',
                'course.onlineDetail:id,course_id,platform,meeting_link,meeting_password',
                'liveAccess',
            ])
            ->orderByRaw('COALESCE(courses.start_date, participants.created_at) DESC')
            ->orderByDesc('participants.id');

        if ($typ === 'paid') {
            $query->whereNotNull('courses.id')->where('courses.is_paid', 1);
        } elseif ($typ === 'free') {
            $query->whereNotNull('courses.id')->where('courses.is_paid', 0);
        }

        $participants = $query->paginate(15)->withQueryString();
        self::hydrateEndedCourseFileLinksForListing($participants);
        self::hydrateTrainingVideoNotesCountsForListing($participants);
        self::hydrateLiveAccessForListing($participants);

        return [
            'participants' => $participants,
            'szkoleniaTyp' => $typ,
            'szkoleniaCounts' => $szkoleniaCounts,
        ];
    }

    private static function hydrateLiveAccessForListing(LengthAwarePaginator $participants): void
    {
        $service = app(DashboardCourseLiveAccessService::class);

        foreach ($participants->getCollection() as $participant) {
            $live = $service->forParticipant($participant);
            $participant->setAttribute('dashboard_live_access', $live);
        }
    }

    private static function hydrateEndedCourseFileLinksForListing(LengthAwarePaginator $participants): void
    {
        $tz = config('app.timezone');

        foreach ($participants->getCollection() as $participant) {
            if ($participant->course) {
                $participant->course->setRelation('fileLinks', collect());
            }
        }

        $courseIds = $participants->getCollection()
            ->filter(function (Participant $participant) use ($tz) {
                $course = $participant->course;
                if (! $course || ! $course->end_date || ! $participant->hasActiveAccess()) {
                    return false;
                }

                return Carbon::parse($course->end_date)->timezone($tz)->isPast()
                    && ($course->file_links_count ?? 0) > 0;
            })
            ->pluck('course_id')
            ->unique()
            ->values();

        if ($courseIds->isEmpty()) {
            return;
        }

        $linksByCourseId = CourseFileLink::query()
            ->select(['id', 'course_id', 'title', 'url', 'order'])
            ->whereIn('course_id', $courseIds)
            ->orderBy('order')
            ->get()
            ->groupBy('course_id');

        foreach ($participants->getCollection() as $participant) {
            $course = $participant->course;
            if (! $course || ! $linksByCourseId->has($course->id)) {
                continue;
            }

            $course->setRelation('fileLinks', $linksByCourseId->get($course->id));
        }
    }

    private static function hydrateTrainingVideoNotesCountsForListing(LengthAwarePaginator $participants): void
    {
        $participantIds = $participants->getCollection()->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($participantIds === []) {
            return;
        }

        $countsByParticipantId = ParticipantTrainingVideoNote::query()
            ->whereIn('participant_id', $participantIds)
            ->selectRaw('participant_id, COUNT(*) as notes_count')
            ->groupBy('participant_id')
            ->pluck('notes_count', 'participant_id');

        foreach ($participants->getCollection() as $participant) {
            $participant->setAttribute(
                'training_video_notes_count',
                (int) ($countsByParticipantId[(int) $participant->id] ?? 0)
            );
        }
    }
}
