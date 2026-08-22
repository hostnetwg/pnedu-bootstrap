<?php

namespace App\Services;

use App\Models\FormOrder;
use App\Models\FormOrderParticipant;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Uczestnicy publicznego formularza zamówienia: parsowanie, limit, unikalność e-maila, cena × N.
 *
 * Rabat grupowy nie jest liczony (v1) — hook {@see totalPrice()} zostawia miejsce na przyszłą zniżkę
 * przy wariancie cenowym.
 */
class OrderFormParticipantService
{
    public function maxCount(): int
    {
        $max = (int) config('order_form.max_participants', 50);

        return $max > 0 ? $max : 50;
    }

    public function allowsMultiple(string $buyerType): bool
    {
        return $buyerType === 'organisation';
    }

    /**
     * @return array<string, mixed>
     */
    public function validationRules(string $buyerType): array
    {
        $max = $this->allowsMultiple($buyerType) ? $this->maxCount() : 1;

        return [
            'participants' => ['nullable', 'array', 'min:1', 'max:'.$max],
            'participants.*.first_name' => ['required_with:participants', 'string', 'max:255'],
            'participants.*.last_name' => ['required_with:participants', 'string', 'max:255'],
            'participants.*.email' => ['required_with:participants', 'email', 'max:255'],
            'participant_first_name' => ['required_without:participants', 'nullable', 'string', 'max:255'],
            'participant_last_name' => ['required_without:participants', 'nullable', 'string', 'max:255'],
            'participant_email' => ['required_without:participants', 'nullable', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationMessages(): array
    {
        $max = $this->maxCount();

        return [
            'participants.max' => "Na jednym zamówieniu można zapisać maksymalnie {$max} uczestników.",
            'participants.min' => 'Podaj dane przynajmniej jednego uczestnika szkolenia.',
            'participants.*.first_name.required_with' => 'Imię uczestnika jest wymagane.',
            'participants.*.last_name.required_with' => 'Nazwisko uczestnika jest wymagane.',
            'participants.*.email.required_with' => 'E-mail uczestnika jest wymagany.',
            'participants.*.email.email' => 'Podaj prawidłowy adres e-mail uczestnika.',
            'participant_first_name.required_without' => 'Imię uczestnika jest wymagane.',
            'participant_last_name.required_without' => 'Nazwisko uczestnika jest wymagane.',
            'participant_email.required_without' => 'E-mail uczestnika jest wymagany.',
            'participant_email.email' => 'Podaj prawidłowy adres e-mail uczestnika.',
        ];
    }

    /**
     * @return list<array{first_name: string, last_name: string, email: string}>
     */
    public function parseFromRequest(Request $request, string $buyerType): array
    {
        $raw = $request->input('participants');
        $rows = [];

        if (is_array($raw) && $raw !== []) {
            foreach ($raw as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $first = trim((string) ($row['first_name'] ?? ''));
                $last = trim((string) ($row['last_name'] ?? ''));
                $email = strtolower(trim((string) ($row['email'] ?? '')));
                if ($first === '' && $last === '' && $email === '') {
                    continue;
                }
                $rows[] = [
                    'first_name' => $first,
                    'last_name' => $last,
                    'email' => $email,
                ];
            }
        }

        if ($rows === []) {
            $rows[] = [
                'first_name' => trim((string) $request->input('participant_first_name', '')),
                'last_name' => trim((string) $request->input('participant_last_name', '')),
                'email' => strtolower(trim((string) $request->input('participant_email', ''))),
            ];
        }

        if (! $this->allowsMultiple($buyerType)) {
            $rows = array_slice($rows, 0, 1);
        }

        $max = $this->maxCount();
        if (count($rows) > $max) {
            $rows = array_slice($rows, 0, $max);
        }

        return array_values($rows);
    }

    /**
     * @param  list<array{first_name: string, last_name: string, email: string}>  $rows
     * @return array{first_name: string, last_name: string, email: string}
     */
    public function primaryRow(array $rows): array
    {
        return $rows[0] ?? [
            'first_name' => '',
            'last_name' => '',
            'email' => '',
        ];
    }

    /**
     * Kwota całkowita zamówienia. Miejsce na przyszły rabat grupowy (np. z wariantu cenowego).
     */
    public function totalPrice(?float $unitPrice, int $participantCount): ?float
    {
        if ($unitPrice === null) {
            return null;
        }

        $count = max(1, $participantCount);

        return round($unitPrice * $count, 2);
    }

    /**
     * @param  list<array{first_name: string, last_name: string, email: string}>  $rows
     *
     * @throws ValidationException
     */
    public function assertEmailsAvailable(int $courseId, array $rows, ?int $exceptFormOrderId = null): void
    {
        $errors = [];
        $seen = [];

        foreach ($rows as $index => $row) {
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            $key = 'participants.'.$index.'.email';
            if ($index === 0 && $email === '') {
                $key = 'participant_email';
            }

            if ($email === '' || ! str_contains($email, '@')) {
                continue;
            }

            if (isset($seen[$email])) {
                $errors[$key] = 'Ten sam adres e-mail nie może powtórzyć się na zamówieniu. Każdy uczestnik musi mieć własny e-mail.';

                continue;
            }
            $seen[$email] = $index;

            $conflict = $this->emailConflictOnCourse($courseId, $email, $exceptFormOrderId);
            if ($conflict !== null) {
                $errors[$key] = $conflict;
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @return array{available: bool, message: string|null}
     */
    public function emailAvailability(int $courseId, string $email, ?int $exceptFormOrderId = null): array
    {
        $email = strtolower(trim($email));
        if ($email === '' || ! str_contains($email, '@')) {
            return ['available' => true, 'message' => null];
        }

        $message = $this->emailConflictOnCourse($courseId, $email, $exceptFormOrderId);

        return [
            'available' => $message === null,
            'message' => $message,
        ];
    }

    /**
     * @param  list<array{first_name: string, last_name: string, email: string}>  $rows
     */
    public function sync(FormOrder $order, array $rows): void
    {
        FormOrderParticipant::syncManyFromFormOrder($order, $rows);

        $unprovisioned = $order->participants()
            ->whereNull('participant_id')
            ->whereRaw("TRIM(participant_email) != ''")
            ->exists();

        if ($unprovisioned && $order->pnedu_provisioned_at !== null) {
            $order->pnedu_provisioned_at = null;
            $order->save();
        }
    }

    private function emailConflictOnCourse(int $courseId, string $email, ?int $exceptFormOrderId): ?string
    {
        $onThisOrder = false;
        if ($exceptFormOrderId) {
            $onThisOrder = FormOrderParticipant::query()
                ->where('form_order_id', $exceptFormOrderId)
                ->whereNull('deleted_at')
                ->whereRaw('LOWER(TRIM(participant_email)) = ?', [$email])
                ->exists();
        }

        $enrolled = Participant::query()
            ->where('course_id', $courseId)
            ->where(function ($q) use ($email) {
                $q->where('email_normalized', $email)
                    ->orWhereRaw('LOWER(TRIM(email)) = ?', [$email]);
            })
            ->exists();

        if ($enrolled && ! $onThisOrder) {
            return 'Adres '.$email.' jest już zapisany na to szkolenie. Użyj innego e-maila albo skontaktuj się z biurem.';
        }

        $otherOrder = FormOrderParticipant::query()
            ->whereNull('deleted_at')
            ->whereRaw('LOWER(TRIM(participant_email)) = ?', [$email])
            ->whereHas('formOrder', function ($q) use ($courseId, $exceptFormOrderId) {
                $q->where('product_id', $courseId)
                    ->whereNull('deleted_at');

                app(FormOrderOnlineAbandonmentService::class)
                    ->scopeOrdersBlockingParticipantEmail($q, $exceptFormOrderId);
            })
            ->exists();

        if ($otherOrder && ! $onThisOrder) {
            return 'Adres '.$email.' jest już zgłoszony na to szkolenie w innym zamówieniu.';
        }

        return null;
    }
}
