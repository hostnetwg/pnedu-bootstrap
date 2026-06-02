<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('verification_reminder_3d_sent_at')->nullable()->after('email_verified_at');
            $table->timestamp('verification_reminder_83d_sent_at')->nullable()->after('verification_reminder_3d_sent_at');
            $table->timestamp('verification_reminder_89d_sent_at')->nullable()->after('verification_reminder_83d_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'verification_reminder_3d_sent_at',
                'verification_reminder_83d_sent_at',
                'verification_reminder_89d_sent_at',
            ]);
        });
    }
};
