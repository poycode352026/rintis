<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rintis_checkout_guards', function (Blueprint $table): void {
            $table->string('identity_hash', 64)->primary();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rintis_checkout_guards');
    }
};
