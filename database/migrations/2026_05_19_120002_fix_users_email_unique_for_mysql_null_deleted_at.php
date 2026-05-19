<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * MySQL: UNIQUE(email, deleted_at) nie blokuje wielu aktywnych kont (deleted_at = NULL).
     * Kolumna generowana: aktywne konto → slot = email; soft-deleted → email#timestamp.
     */
    public function up(): void
    {
        if ($this->indexExists('users_email_deleted_at_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_email_deleted_at_unique');
            });
        }

        $this->deduplicateActiveUsers();

        if (! Schema::hasColumn('users', 'email_unique_slot')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('email_unique_slot', 320)
                    ->storedAs("IF(deleted_at IS NULL, email, CONCAT(email, '#', DATE_FORMAT(deleted_at, '%Y%m%d%H%i%s')))")
                    ->after('email');
            });
        }

        if (! $this->indexExists('users_email_unique_slot_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('email_unique_slot', 'users_email_unique_slot_unique');
            });
        }

        if (! $this->indexExistsOnColumn('users', 'email')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('email');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('users_email_unique_slot_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_email_unique_slot_unique');
            });
        }

        if (Schema::hasColumn('users', 'email_unique_slot')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('email_unique_slot');
            });
        }

        if ($this->indexExistsOnColumn('users', 'email') && ! $this->indexExists('users_email_deleted_at_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['email']);
            });
        }

        if (! $this->indexExists('users_email_deleted_at_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique(['email', 'deleted_at'], 'users_email_deleted_at_unique');
            });
        }
    }

    private function deduplicateActiveUsers(): void
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
    }

    private function indexExists(string $indexName): bool
    {
        foreach (Schema::getIndexes('users') as $index) {
            if (($index['name'] ?? '') === $indexName) {
                return true;
            }
        }

        return false;
    }

    private function indexExistsOnColumn(string $table, string $column): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            $columns = $index['columns'] ?? [];
            if (count($columns) === 1 && $columns[0] === $column && ($index['unique'] ?? false) === false) {
                return true;
            }
        }

        return false;
    }
};
