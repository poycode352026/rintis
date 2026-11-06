<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rintis_weekly_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->unique()->constrained('rintis_stores')->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->json('days');
            $table->json('product_ids');
            $table->string('last_date', 10)->nullable();
            $table->string('skip_date', 10)->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rintis_weekly_schedules');
    }
};
