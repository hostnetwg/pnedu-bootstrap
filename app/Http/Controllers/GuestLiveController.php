<?php

namespace App\Http\Controllers;

use App\Services\ClickMeetingConferenceEndedCache;
use App\Services\ClickMeetingService;
use App\Services\GuestLiveAccessService;
use App\Services\LiveTransmissionResourceBarService;
use App\Services\LiveTransmissionService;
use App\Support\PostTrainingThankYouPhase;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class GuestLiveController extends Controller
{
    public function show(
        Request $request,
        string $token,
        GuestLiveAccessService $guestLive,
        LiveTransmissionService $liveTransmission
    ): View|Response|RedirectResponse {
        $course = $guestLive->findCourseByToken($token);
        if ($course === null) {
            abort(404);
        }

        $entryError = $guestLive->entryError($course);
        if ($entryError !== null) {
            return $this->failure($entryError, $course->id);
        }

        $identity = $guestLive->registeredIdentity($request, $course);
        if ($identity === null) {
            return view('guest-live.gate', [
                'token' => $token,
                'course' => $course,
                'courseTitle' => $course->plainTitle('Szkolenie'),
            ]);
        }

        $built = $liveTransmission->buildForGuest(
            $course,
            $identity['email'],
            $identity['nickname']
        );
        if (! ($built['ok'] ?? false)) {
            return $this->failure(
                (string) ($built['error'] ?? 'Nie udało się otworzyć transmisji.'),
                $course->id
            );
        }

        $isMobile = (bool) preg_match(
            '/android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini|mobile/i',
            (string) $request->userAgent()
        );
        if ($isMobile) {
            $fallback = $built['room_autologin_url'] ?? null;
            if (is_string($fallback) && $fallback !== '') {
                return redirect()->away($fallback);
            }
        }

        $courseId = (int) $course->id;

        return view('dashboard.szkolenia-transmisja', [
            'participant' => null,
            'course' => $course,
            'courseTitle' => $built['course_title'] ?? ($course->title ?? 'Szkolenie'),
            'iframeSrc' => $built['iframe_src'] ?? null,
            'roomAutologinUrl' => $built['room_autologin_url'] ?? null,
            'roomTokenUrl' => $built['room_token_url'] ?? null,
            'tokenRotated' => false,
            'bareLayout' => false,
            'guestLive' => true,
            'rejoinUrl' => null,
            'postTrainingThankYouUrl' => route('post-training.thank-you', ['course' => $courseId]),
            'meetingStatusUrl' => route('guest-live.meeting-status', ['token' => $token]),
            'meetingStatusPollMs' => LiveTransmissionResourceBarService::VIEWER_POLL_MS,
            'meetingStatusFirstMs' => LiveTransmissionResourceBarService::VIEWER_POLL_FIRST_MS,
            'presenceHeartbeatUrl' => null,
            'presenceLeaveUrl' => null,
            'presenceHeartbeatMs' => 0,
        ]);
    }

    public function register(
        Request $request,
        string $token,
        GuestLiveAccessService $guestLive
    ): RedirectResponse|Response {
        $course = $guestLive->findCourseByToken($token);
        if ($course === null) {
            abort(404);
        }

        $entryError = $guestLive->entryError($course);
        if ($entryError !== null) {
            return $this->failure($entryError, $course->id);
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'rodo_consent' => ['required', 'accepted'],
        ], [
            'first_name.required' => 'Podaj imię.',
            'last_name.required' => 'Podaj nazwisko.',
            'email.required' => 'Podaj adres e-mail.',
            'email.email' => 'Podaj prawidłowy adres e-mail.',
            'rodo_consent.accepted' => 'Musisz wyrazić zgodę na przetwarzanie danych osobowych.',
        ]);

        $participant = $guestLive->registerOrUpdate(
            $course,
            $validated['first_name'],
            $validated['last_name'],
            $validated['email']
        );
        $guestLive->remember($request, $course, $participant);

        return redirect()->route('guest-live.show', ['token' => $token]);
    }

    public function meetingStatus(
        Request $request,
        string $token,
        GuestLiveAccessService $guestLive,
        ClickMeetingService $clickMeeting,
        LiveTransmissionResourceBarService $resourceBar
    ): JsonResponse {
        $course = $guestLive->findCourseByToken($token);
        if ($course === null) {
            return response()->json([
                'ok' => false,
                'ended' => false,
                'error' => 'not_found',
                'resource_links' => [],
                'live_offer' => null,
            ], 404);
        }

        if ($guestLive->registeredIdentity($request, $course) === null) {
            return response()->json([
                'ok' => false,
                'ended' => false,
                'error' => 'register',
                'resource_links' => [],
                'live_offer' => null,
            ], 401);
        }

        $entryError = $guestLive->entryError($course);
        if ($entryError !== null) {
            return response()->json([
                'ok' => false,
                'ended' => false,
                'error' => $entryError,
                'resource_links' => [],
                'live_offer' => null,
            ], 403);
        }

        $course->loadMissing('onlineDetail');
        $eventId = trim((string) ($course->onlineDetail?->clickmeeting_event_id ?? ''));
        $courseId = (int) $course->id;
        $thankYouUrl = route('post-training.thank-you', ['course' => $courseId]);
        $payload = $resourceBar->viewerPayload($course, forGuest: true);
        $resourceLinks = $payload['resource_links'];
        $liveOffer = $payload['live_offer'];

        if ($eventId === '') {
            return response()->json([
                'ok' => true,
                'ended' => false,
                'thank_you_url' => $thankYouUrl,
                'resource_links' => $resourceLinks,
                'live_offer' => $liveOffer,
            ]);
        }

        $status = $clickMeeting->isConferenceEnded($eventId);

        if (($status['ended'] ?? false) && $eventId !== '') {
            $start = $course->start_date;
            $expiresAt = $start instanceof \Carbon\CarbonInterface
                ? PostTrainingThankYouPhase::effectiveEnd(
                    $course,
                    Carbon::parse($start)->timezone((string) config('app.timezone', 'Europe/Warsaw'))
                )->copy()->addDay()
                : now()->addDay();

            ClickMeetingConferenceEndedCache::mark($eventId, $expiresAt);
        }

        return response()->json([
            'ok' => (bool) ($status['success'] ?? false),
            'ended' => (bool) ($status['ended'] ?? false),
            'status' => $status['status'] ?? null,
            'thank_you_url' => $thankYouUrl,
            'error' => $status['error'] ?? null,
            'resource_links' => $resourceLinks,
            'live_offer' => $liveOffer,
        ]);
    }

    private function failure(string $message, ?int $courseId = null): Response
    {
        $exitUrl = $courseId
            ? route('post-training.thank-you', ['course' => $courseId])
            : url('/');

        return response()->view('dashboard.szkolenia-transmisja-error', [
            'message' => $message,
            'exitUrl' => $exitUrl,
        ]);
    }
}
