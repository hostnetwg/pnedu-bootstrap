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
        Schema::connection('pneadm')->table('courses', function (Blueprint $table) {
            if (!Schema::connection('pneadm')->hasColumn('courses', 'publigo_product_id')) {
                // Sprawdź czy istnieje kolumna certificate_template_id, jeśli nie - dodaj na końcu
                $afterColumn = Schema::connection('pneadm')->hasColumn('courses', 'certificate_template_id') 
                    ? 'certificate_template_id' 
                    : 'certificate_format';
                $table->integer('publigo_product_id')->nullable()->after($afterColumn);
            }
            if (!Schema::connection('pneadm')->hasColumn('courses', 'publigo_price_id')) {
                $table->integer('publigo_price_id')->nullable()->after('publigo_product_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('pneadm')->table('courses', function (Blueprint $table) {
            $table->dropColumn(['publigo_product_id', 'publigo_price_id']);
        });
    }
};
