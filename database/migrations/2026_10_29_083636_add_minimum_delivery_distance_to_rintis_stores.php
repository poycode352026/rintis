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
        Schema::table('rintis_stores', function (Blueprint $table) {
            $table->unsignedInteger('delivery_min_distance_m')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rintis_stores', function (Blueprint $table) {
            $table->dropColumn('delivery_min_distance_m');
        });
    }
};
