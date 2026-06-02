<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'email_unique_slot')) {
            return;
        }

        DB::table('users')
            ->whereNotNull('deleted_at')
            ->update([
                'email_unique_slot' => DB::raw("CONCAT(LOWER(TRIM(email)), '#', deleted_at)"),
            ]);
    }

    public function down(): void
    {
        // Nie przywracamy błędnego stanu, w którym soft-deleted użytkownik blokował aktywny e-mail.
    }
};
