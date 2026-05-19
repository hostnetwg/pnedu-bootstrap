<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jeden aktywny użytkownik (deleted_at IS NULL) na adres e-mail;
     * po soft delete ten sam e-mail może wrócić (inny deleted_at w kluczu).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->unique(['email', 'deleted_at'], 'users_email_deleted_at_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_email_deleted_at_unique');
            $table->index('email');
        });
    }
};
