<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rintis_orders', function (Blueprint $table): void {
            $table->json('fulfillment_history')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('rintis_orders', function (Blueprint $table): void {
            $table->dropColumn('fulfillment_history');
        });
    }
};
