<?php

namespace App\Services;

use App\Models\Course;
use App\Models\FormOrderParticipant;
use App\Models\Participant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CourseRegistrationAvailabilityService
{
    public function isClosed(Course $course): bool
    {
        return $course->hasClosedRegistration();
    }

    public function successor(Course $course): ?Course
    {
        if (! $this->isClosed($course) || empty($course->registration_successor_course_id)) {
            return null;
        }

        $successor = $course->relationLoaded('registrationSuccessor')
            ? $course->registrationSuccessor
            : $course->registrationSuccessor()->first();

        if (! $successor instanceof Course || $successor->hasClosedRegistration()) {
            return null;
        }

        return $successor;
    }

    public function redirectToSuccessorForm(
        Course $course,
        Request $request,
        string $routeName
    ): ?RedirectResponse {
        if (! $this->isClosed($course)) {
            return null;
        }

        $successor = $this->successor($course);

        if (! $successor) {
            return redirect()
                ->route('courses.show', $course->id)
                ->with('info', $this->closedMessage($course));
        }

        $query = $request->query();
        unset($query['price_variant_id'], $query['registration_redirected_from']);
        $query['registration_redirected_from'] = (string) $course->id;

        $params = array_merge(['id' => $successor->id], $query);

        return redirect()
            ->route($routeName, $params)
            ->with('info', $this->redirectMessage($course, $successor));
    }

    public function redirectMessage(Course $closedCourse, Course $successor): string
    {
        return $this->closedMessage($closedCourse, $successor).' Poniżej widzisz formularz zapisu dla nowego terminu.';
    }

    public function closedMessage(Course $closedCourse, ?Course $successor = null): string
    {
        $message = 'Na szkolenie w terminie '.$this->courseDateLabel($closedCourse).' nie mamy już wolnych miejsc.';
        $customMessage = trim((string) ($closedCourse->registration_closed_message ?? ''));

        if ($customMessage !== '') {
            $message .= ' '.$customMessage;
        }

        if ($successor instanceof Course) {
            $message .= ' Zapisz się na kolejną edycję w terminie '
                .$this->courseDateLabel($successor).'.';
        }

        return $message;
    }

    public function courseDateLabel(Course $course): string
    {
        return $course->start_date
            ? $course->start_date->format('d.m.Y H:i')
            : 'termin wkrótce';
    }

    public function occupiedSeats(Course $course): int
    {
        $participants = Participant::query()
            ->where('course_id', $course->id)
            ->count();

        $orderParticipantQuery = FormOrderParticipant::query()
            ->whereHas('formOrder', function ($q) use ($course) {
                $q->where('product_id', $course->id)
                    ->whereNull('deleted_at');

                app(FormOrderOnlineAbandonmentService::class)
                    ->scopeOrdersBlockingParticipantEmail($q);
            });

        return $participants + $orderParticipantQuery->count();
    }
}
