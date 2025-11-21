<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('admpnedu')->table('courses', function (Blueprint $table) {
            $table->integer('publigo_product_id')->nullable()->after('certificate_template_id');
            $table->integer('publigo_price_id')->nullable()->after('publigo_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('admpnedu')->table('courses', function (Blueprint $table) {
            $table->dropColumn(['publigo_product_id', 'publigo_price_id']);
        });
    }
};
