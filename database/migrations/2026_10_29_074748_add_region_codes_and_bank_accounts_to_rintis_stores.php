<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rintis_stores', function (Blueprint $table): void {
            $table->string('province_code', 20)->nullable();
            $table->string('city_code', 20)->nullable();
            $table->string('district_code', 20)->nullable();
        });
        Schema::create('rintis_bank_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained('rintis_stores')->restrictOnDelete();
            $table->string('bank_name', 100);
            $table->string('account_holder', 150);
            $table->string('account_number', 34);
            $table->string('instructions', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rintis_bank_accounts');
        Schema::table('rintis_stores', function (Blueprint $table): void {
            $table->dropColumn(['province_code', 'city_code', 'district_code']);
        });
    }
};
