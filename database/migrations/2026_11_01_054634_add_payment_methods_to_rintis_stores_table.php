<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rintis_stores', function (Blueprint $table): void {
            $table->boolean('cash_enabled')->default(true);
            $table->boolean('bank_transfer_enabled')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('rintis_stores', function (Blueprint $table): void {
            $table->dropColumn(['cash_enabled', 'bank_transfer_enabled']);
        });
    }
};
