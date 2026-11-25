<?php

namespace App\Http\Controllers;

use App\Models\ClosedTrainingCase;
use App\Models\Course;
use App\Models\Instructor;
use App\Models\TrainerPortalNotification;
use App\Services\TrainerScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TrainerPortalController extends Controller
{
    public function index(Request $request): View
    {
        $instructor = $this->instructor($request);
        $cases = ClosedTrainingCase::query()
            ->where('instructor_id', $instructor->id)
            ->where('show_in_trainer_panel', true)
            ->orderByDesc('trainer_panel_shared_at')
            ->orderByDesc('id')
            ->get();

        return view('trainer.index', [
            'instructor' => $instructor,
            'cases' => $cases,
        ]);
    }

    public function calendar(Request $request): View
    {
        $instructor = $this->instructor($request);
        $courses = Course::query()
            ->where('instructor_id', $instructor->id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->where('end_date', '>=', now())
            ->orderBy('start_date')
            ->get();

        $locations = DB::connection('pneadm')
            ->table('course_locations')
            ->whereIn('course_id', $courses->pluck('id'))
            ->whereNull('deleted_at')
            ->get()
            ->keyBy('course_id');

        return view('trainer.calendar', [
            'instructor' => $instructor,
            'courses' => $courses,
            'locations' => $locations,
        ]);
    }

    public function show(Request $request, int $case): View
    {
        $trainingCase = $this->visibleCase($request, $case);
        $this->markCaseRead($request, $trainingCase);

        return view('trainer.show', [
            'instructor' => $this->instructor($request),
            'case' => $trainingCase,
            'locked' => $this->linkedToCourse($trainingCase),
        ]);
    }

    public function schedule(Request $request, int $case, TrainerScheduleService $schedules): RedirectResponse
    {
        $trainingCase = $this->visibleCase($request, $case);
        $data = $request->validate([
            'start_at' => ['required', 'date'],
            'trainer_note' => ['nullable', 'string', 'max:3000'],
            'schedule_test' => ['nullable', 'boolean'],
        ], [
            'trainer_note.max' => 'Notatka może mieć najwyżej 3000 znaków.',
        ]);

        $result = $schedules->record(
            $trainingCase,
            $this->instructor($request),
            Carbon::parse($data['start_at'], config('app.timezone')),
            $data['trainer_note'] ?? null,
            $request->boolean('schedule_test'),
        );

        $message = $result['date_changed']
            ? 'Termin zapisany. Administrator dostał powiadomienie.'
            : ($result['note_changed'] ? 'Notatka zapisana.' : 'Termin zapisany.');

        return redirect()
            ->route('trainer.cases.show', $trainingCase)
            ->with('success', $message);
    }

    public function decline(Request $request, int $case, TrainerScheduleService $schedules): RedirectResponse
    {
        $trainingCase = $this->visibleCase($request, $case);
        $declined = $request->boolean('trainer_declined');
        $data = $request->validate([
            'trainer_decline_reason' => [$declined ? 'required' : 'nullable', 'string', 'max:500'],
        ], [
            'trainer_decline_reason.required' => 'Napisz krótko, dlaczego rezygnujesz.',
            'trainer_decline_reason.max' => 'Wyjaśnienie może mieć najwyżej 500 znaków.',
        ]);

        $result = $schedules->decline(
            $trainingCase,
            $this->instructor($request),
            $declined,
            $data['trainer_decline_reason'] ?? null,
        );

        $message = match ($result) {
            'declined' => 'Zapisano rezygnację. Administrator dostał powiadomienie.',
            'withdrawn' => 'Wycofano rezygnację. Administrator dostał powiadomienie.',
            default => 'Bez zmian.',
        };

        return redirect()
            ->route('trainer.cases.show', $trainingCase)
            ->with('success', $message);
    }

    public function notifications(Request $request): View
    {
        $notifications = TrainerPortalNotification::query()
            ->where('audience', TrainerPortalNotification::AUDIENCE_TRAINER)
            ->where('pnedu_user_id', $request->user()->id)
            ->latest()
            ->limit(50)
            ->get();

        return view('trainer.notifications', [
            'notifications' => $notifications,
        ]);
    }

    public function openNotification(Request $request, int $notification): RedirectResponse
    {
        $item = TrainerPortalNotification::query()
            ->whereKey($notification)
            ->where('audience', TrainerPortalNotification::AUDIENCE_TRAINER)
            ->where('pnedu_user_id', $request->user()->id)
            ->firstOrFail();

        if ($item->read_at === null) {
            $item->read_at = now();
            $item->save();
        }

        if ($item->closed_training_case_id) {
            return redirect()->route('trainer.cases.show', $item->closed_training_case_id);
        }

        return redirect()->route('trainer.notifications');
    }

    private function instructor(Request $request): Instructor
    {
        $instructor = $request->attributes->get('trainerInstructor');
        abort_unless($instructor instanceof Instructor, 403);

        return $instructor;
    }

    private function visibleCase(Request $request, int $caseId): ClosedTrainingCase
    {
        return ClosedTrainingCase::query()
            ->whereKey($caseId)
            ->where('instructor_id', $this->instructor($request)->id)
            ->where('show_in_trainer_panel', true)
            ->firstOrFail();
    }

    private function markCaseRead(Request $request, ClosedTrainingCase $case): void
    {
        TrainerPortalNotification::query()
            ->where('audience', TrainerPortalNotification::AUDIENCE_TRAINER)
            ->where('pnedu_user_id', $request->user()->id)
            ->where('closed_training_case_id', $case->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    private function linkedToCourse(ClosedTrainingCase $case): bool
    {
        return DB::connection('pneadm')
            ->table('courses')
            ->where('closed_training_case_id', $case->id)
            ->exists();
    }
}
