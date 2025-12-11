<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
        });

        // Przenieś dane z name do first_name i last_name dla istniejących użytkowników
        DB::table('users')->whereNotNull('name')->get()->each(function ($user) {
            $nameParts = explode(' ', $user->name, 2);
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'first_name' => $nameParts[0] ?? null,
                    'last_name' => $nameParts[1] ?? null,
                ]);
        });

        // Usuń kolumnę name - nie jest już potrzebna
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Przywróć kolumnę name
            $table->string('name')->nullable()->after('id');
        });

        // Przywróć name z first_name i last_name
        DB::table('users')->whereNotNull('first_name')->get()->each(function ($user) {
            $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            DB::table('users')
                ->where('id', $user->id)
                ->update(['name' => $fullName ?: null]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};
