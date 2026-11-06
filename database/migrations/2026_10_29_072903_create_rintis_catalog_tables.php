<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rintis_catalog_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained('rintis_stores')->restrictOnDelete();
            $table->string('name', 100);
            $table->timestamps();
            $table->unique(['store_id', 'name']);
            $table->unique(['id', 'store_id']);
        });
        Schema::create('rintis_catalog_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained('rintis_stores')->restrictOnDelete();
            $table->unsignedBigInteger('catalog_category_id');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price');
            $table->string('image_path')->nullable();
            $table->boolean('is_visible')->default(false);
            $table->timestamps();
            $table->foreign(['catalog_category_id', 'store_id'])->references(['id', 'store_id'])->on('rintis_catalog_categories')->restrictOnDelete();
            $table->index(['store_id', 'is_visible']);
            $table->index(['catalog_category_id', 'store_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rintis_catalog_products');
        Schema::dropIfExists('rintis_catalog_categories');
    }
};
