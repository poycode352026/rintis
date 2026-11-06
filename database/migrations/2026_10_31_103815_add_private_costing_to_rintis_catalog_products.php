<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rintis_catalog_products', function (Blueprint $table): void {
            $table->json('costing')->nullable();
            $table->boolean('is_visible')->default(true)->change();
        });
        DB::table('rintis_catalog_products')->update(['is_visible' => true]);
    }

    public function down(): void
    {
        Schema::table('rintis_catalog_products', function (Blueprint $table): void {
            $table->dropColumn('costing');
            $table->boolean('is_visible')->default(false)->change();
        });
    }
};
