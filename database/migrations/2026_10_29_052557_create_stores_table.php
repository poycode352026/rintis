<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rintis_stores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_id')->unique()->constrained('users')->restrictOnDelete();
            $table->string('name', 100);
            $table->string('slug', 80)->unique();
            $table->string('tagline', 180)->nullable();
            $table->boolean('is_listed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rintis_stores');
    }
};
