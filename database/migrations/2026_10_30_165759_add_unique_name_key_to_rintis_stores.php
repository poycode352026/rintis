<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $keys = [];
        foreach (DB::table('rintis_stores')->orderBy('id')->get(['id', 'name']) as $store) {
            $key = Str::lower(Str::squish($store->name));
            if (isset($keys[$key])) {
                throw new RuntimeException('Duplicate store names must be resolved before applying unique store names.');
            }
            $keys[$key] = $store->id;
        }
        Schema::table('rintis_stores', function (Blueprint $table): void {
            $table->string('name_key', 100)->nullable();
        });
        foreach ($keys as $key => $id) {
            DB::table('rintis_stores')->where('id', $id)->update(['name_key' => (string) $key]);
        }
        Schema::table('rintis_stores', function (Blueprint $table): void {
            $table->string('name_key', 100)->nullable(false)->change();
            $table->unique('name_key');
        });
    }

    public function down(): void
    {
        Schema::table('rintis_stores', function (Blueprint $table): void {
            $table->dropUnique(['name_key']);
            $table->dropColumn('name_key');
        });
    }
};
