<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Normalizacja e-maili oraz usunięcie (soft delete) nadmiarowych aktywnych duplikatów
     * przed dodaniem indeksu UNIQUE(email, deleted_at).
     */
    public function up(): void
    {
        $duplicateGroups = DB::table('users')
            ->selectRaw('LOWER(TRIM(email)) AS norm_email')
            ->whereNull('deleted_at')
            ->groupByRaw('LOWER(TRIM(email))')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('norm_email');

        foreach ($duplicateGroups as $normEmail) {
            $rows = DB::table('users')
                ->whereNull('deleted_at')
                ->whereRaw('LOWER(TRIM(email)) = ?', [$normEmail])
                ->orderByRaw('CASE WHEN email_verified_at IS NOT NULL THEN 0 ELSE 1 END')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(['id']);

            $keeperId = $rows->first()->id;
            $duplicateIds = $rows->pluck('id')->filter(fn ($id) => $id !== $keeperId);

            $deletedAt = now();
            foreach ($duplicateIds as $duplicateId) {
                DB::table('users')
                    ->where('id', $duplicateId)
                    ->update([
                        'remember_token' => null,
                        'deleted_at' => $deletedAt,
                    ]);
                $deletedAt = $deletedAt->copy()->addSecond();
            }

            DB::table('users')
                ->where('id', $keeperId)
                ->update(['email' => $normEmail]);
        }

        DB::table('users')->update([
            'email' => DB::raw('LOWER(TRIM(email))'),
        ]);
    }

    public function down(): void
    {
        // Nie cofamy scalania duplikatów ani normalizacji — dane historyczne.
    }
};
