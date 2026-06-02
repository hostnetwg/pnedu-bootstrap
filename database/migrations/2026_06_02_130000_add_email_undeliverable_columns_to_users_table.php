<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_undeliverable_at')->nullable()->after('email_verified_at');
            $table->string('email_undeliverable_reason', 64)->nullable()->after('email_undeliverable_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_undeliverable_at',
                'email_undeliverable_reason',
            ]);
        });
    }
};
