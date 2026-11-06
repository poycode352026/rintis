<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rintis_store_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->timestamps();
        });

        foreach ([
            'coffee-drinks' => 'Coffee & Drinks',
            'food-groceries' => 'Food & Groceries',
            'fashion-accessories' => 'Fashion & Accessories',
            'home-living' => 'Home & Living',
            'hardware-building' => 'Hardware & Building Materials',
            'services' => 'Services',
            'other' => 'Other',
        ] as $slug => $name) {
            DB::table('rintis_store_categories')->insert([
                'name' => $name, 'slug' => $slug, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        Schema::table('rintis_stores', function (Blueprint $table): void {
            $table->foreignId('category_id')->nullable()->constrained('rintis_store_categories')->restrictOnDelete();
            $table->string('province', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('address_line', 300)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('show_full_address')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('rintis_stores', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn(['province', 'city', 'district', 'address_line', 'latitude', 'longitude', 'show_full_address']);
        });
        Schema::dropIfExists('rintis_store_categories');
    }
};
