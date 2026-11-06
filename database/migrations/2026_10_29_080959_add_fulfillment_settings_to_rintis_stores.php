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
            $table->boolean('pickup_enabled')->default(false);
            $table->text('pickup_instructions')->nullable();
            $table->boolean('delivery_enabled')->default(false);
            $table->string('delivery_fee_mode', 20)->nullable();
            $table->unsignedInteger('delivery_fixed_fee')->nullable();
            $table->unsignedInteger('delivery_rate_per_km')->nullable();
            $table->unsignedInteger('delivery_minimum_fee')->nullable();
            $table->unsignedInteger('delivery_max_distance_m')->nullable();
            $table->timestamp('setup_completed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rintis_stores', function (Blueprint $table) {
            $table->dropColumn(['pickup_enabled', 'pickup_instructions', 'delivery_enabled', 'delivery_fee_mode', 'delivery_fixed_fee', 'delivery_rate_per_km', 'delivery_minimum_fee', 'delivery_max_distance_m', 'setup_completed_at']);
        });
    }
};
