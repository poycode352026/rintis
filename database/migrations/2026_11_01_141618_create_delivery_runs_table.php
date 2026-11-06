<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rintis_delivery_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained('rintis_stores')->cascadeOnDelete();
            $table->string('status')->default('planned');
            $table->json('stops');
            $table->json('origin');
            $table->unsignedBigInteger('distance_m');
            $table->timestamps();
            $table->index(['store_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rintis_delivery_runs');
    }
};
