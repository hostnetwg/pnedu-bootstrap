<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderEntryPlacementService
{
    public const SESSION_KEY = 'marketing.conversion_placement';

    public const PLACEMENT_DASHBOARD_SIDEBAR = 'dashboard_sidebar';

    public function isAllowed(?string $placement): bool
    {
        if ($placement === null || trim($placement) === '') {
            return false;
        }

        return array_key_exists(trim($placement), (array) config('marketing.conversion_placements', []));
    }

    /**
     * Zapisuje placement z ?entry=… powiązany z konkretnym kursem (nie nadpisuje kampanii UTM).
     */
    public function captureFromRequest(Request $request): void
    {
        if (! $request->hasSession()) {
            return;
        }

        $entry = $request->query('entry');
        if (! is_string($entry) || trim($entry) === '') {
            return;
        }

        $entry = trim($entry);
        if (! $this->isAllowed($entry)) {
            return;
        }

        $courseId = $request->route('id');
        if ($courseId === null || ! ctype_digit((string) $courseId)) {
            return;
        }

        $request->session()->put(self::SESSION_KEY, [
            'placement' => $entry,
            'course_id' => (int) $courseId,
        ]);
    }

    public function resolveForCourse(Request $request, int $courseId, ?string $fromForm = null): ?string
    {
        if (is_string($fromForm) && trim($fromForm) !== '' && $this->isAllowed(trim($fromForm))) {
            return Str::limit(trim($fromForm), 50, '');
        }

        if (! $request->hasSession()) {
            return null;
        }

        $data = $request->session()->get(self::SESSION_KEY);
        if (! is_array($data)) {
            return null;
        }

        if ((int) ($data['course_id'] ?? 0) !== $courseId) {
            return null;
        }

        $placement = $data['placement'] ?? null;

        return is_string($placement) && $this->isAllowed($placement)
            ? Str::limit($placement, 50, '')
            : null;
    }

    public function clear(Request $request): void
    {
        if ($request->hasSession()) {
            $request->session()->forget(self::SESSION_KEY);
        }
    }
}
