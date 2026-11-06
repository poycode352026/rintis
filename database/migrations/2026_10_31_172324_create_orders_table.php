<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rintis_open_order_items', function (Blueprint $table): void {
            $table->unsignedInteger('ordered_quantity')->default(0);
        });
        Schema::create('rintis_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained('rintis_stores')->restrictOnDelete();
            $table->foreignId('open_order_id')->constrained('rintis_open_orders')->restrictOnDelete();
            $table->string('reference', 24)->unique();
            $table->uuid('idempotency_key')->unique();
            $table->string('guest_key', 64);
            $table->string('access_hash', 64)->unique();
            $table->text('access_token');
            $table->string('customer_name', 100);
            $table->string('phone', 30);
            $table->string('method', 20);
            $table->string('payment_method', 20)->default('cash');
            $table->string('status', 20)->default('received');
            $table->json('items');
            $table->json('fulfillment');
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('delivery_fee');
            $table->unsignedBigInteger('total');
            $table->timestamps();
            $table->index(['store_id', 'open_order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rintis_orders');
        Schema::table('rintis_open_order_items', function (Blueprint $table): void {
            $table->dropColumn('ordered_quantity');
        });
    }
};
