<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rintis_open_order_items', function (Blueprint $table) {
            $table->string('image_path')->nullable();
        });
        DB::table('rintis_open_order_items')->orderBy('id')->each(function ($item): void {
            $path = DB::table('rintis_catalog_products')->where('id', $item->catalog_product_id)->value('image_path');
            DB::table('rintis_open_order_items')->where('id', $item->id)->update(['image_path' => $path]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rintis_open_order_items', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
