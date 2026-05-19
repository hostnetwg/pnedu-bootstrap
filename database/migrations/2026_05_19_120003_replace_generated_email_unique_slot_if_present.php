<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Środowiska, gdzie 120002 utworzyła kolumnę GENERATED (DATE_FORMAT) — zamiana na zwykłą kolumnę.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'email_unique_slot')) {
            return;
        }

        if (! $this->isGeneratedColumn('email_unique_slot')) {
            return;
        }

        if ($this->indexExists('users_email_unique_slot_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_email_unique_slot_unique');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_unique_slot');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email_unique_slot', 320)->nullable()->after('email');
        });

        DB::table('users')
            ->whereNull('deleted_at')
            ->update(['email_unique_slot' => DB::raw('LOWER(TRIM(email))')]);

        DB::table('users')
            ->whereNotNull('deleted_at')
            ->update(['email_unique_slot' => DB::raw("CONCAT(LOWER(TRIM(email)), '#', deleted_at)")]);

        DB::statement('ALTER TABLE users MODIFY email_unique_slot VARCHAR(320) NOT NULL');

        Schema::table('users', function (Blueprint $table) {
            $table->unique('email_unique_slot', 'users_email_unique_slot_unique');
        });
    }

    public function down(): void
    {
        // Nie przywracamy kolumny GENERATED.
    }

    private function isGeneratedColumn(string $column): bool
    {
        $row = DB::selectOne(
            'SELECT EXTRA FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?',
            ['users', $column]
        );

        return str_contains(strtoupper((string) ($row->EXTRA ?? '')), 'GENERATED');
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
};
