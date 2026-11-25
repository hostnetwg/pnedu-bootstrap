<?php

namespace App\Support;

use App\Models\Instructor;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TrainerImpersonation
{
    public const SESSION_KEY = 'trainer_preview_instructor_id';

    public static function isPanelAdmin(?User $user): bool
    {
        $email = mb_strtolower(trim((string) $user?->email));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        try {
            return DB::connection('pneadm')->table('users')
                ->join('roles', 'roles.id', '=', 'users.role_id')
                ->whereRaw('LOWER(users.email) = ?', [$email])
                ->whereIn('roles.name', ['admin', 'super_admin'])
                ->where('users.is_active', 1)
                ->whereNull('users.deleted_at')
                ->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    public static function previewInstructor(): ?Instructor
    {
        $user = auth()->user();
        if (! $user instanceof User || ! self::isPanelAdmin($user)) {
            return null;
        }

        $id = (int) session(self::SESSION_KEY, 0);
        if ($id === 0) {
            return null;
        }

        $instructor = Instructor::query()
            ->whereKey($id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->first();

        if ($instructor === null) {
            session()->forget(self::SESSION_KEY);
        }

        return $instructor;
    }

    /**
     * @return Collection<int, Instructor>
     */
    public static function switchList(): Collection
    {
        $user = auth()->user();
        if (! $user instanceof User || ! self::isPanelAdmin($user)) {
            return collect();
        }

        $ownInstructorId = Instructor::query()
            ->where('pnedu_user_id', $user->id)
            ->value('id');

        return Instructor::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->when($ownInstructorId, fn ($query) => $query->where('id', '!=', $ownInstructorId))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'title']);
    }
}
