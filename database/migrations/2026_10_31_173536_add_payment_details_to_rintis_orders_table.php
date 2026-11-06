<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rintis_orders', function (Blueprint $table): void {
            $table->string('payment_status', 30)->default('unpaid');
            $table->json('payment_details')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('rintis_orders', function (Blueprint $table): void {
            $table->dropColumn(['payment_status', 'payment_details']);
        });
    }
};
