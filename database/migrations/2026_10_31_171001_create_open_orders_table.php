<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rintis_open_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained('rintis_stores')->restrictOnDelete();
            $table->string('name', 120);
            $table->string('status', 20)->default('draft');
            $table->timestamp('opens_at');
            $table->timestamp('closes_at');
            $table->json('fulfillment');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['store_id', 'status', 'closes_at']);
        });
        Schema::create('rintis_open_order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('open_order_id')->constrained('rintis_open_orders')->cascadeOnDelete();
            $table->foreignId('catalog_product_id')->constrained('rintis_catalog_products')->restrictOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price');
            $table->unsignedInteger('quota')->nullable();
            $table->json('options')->nullable();
            $table->json('costing')->nullable();
            $table->timestamps();
            $table->unique(['open_order_id', 'catalog_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rintis_open_order_items');
        Schema::dropIfExists('rintis_open_orders');
    }
};
