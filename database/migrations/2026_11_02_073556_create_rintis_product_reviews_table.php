<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rintis_product_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('rintis_orders')->cascadeOnDelete();
            $table->foreignId('catalog_product_id')->nullable()->constrained('rintis_catalog_products')->nullOnDelete();
            $table->unsignedInteger('line_index');
            $table->unsignedTinyInteger('rating');
            $table->timestamps();
            $table->unique(['order_id', 'line_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rintis_product_reviews');
    }
};
