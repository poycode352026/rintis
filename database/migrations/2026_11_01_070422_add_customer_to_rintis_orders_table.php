<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rintis_orders', function (Blueprint $table): void {
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->index(['customer_id', 'id']);
            $table->index(['guest_key', 'id']);
        });
    }

    public function down(): void
    {
        Schema::table('rintis_orders', function (Blueprint $table): void {
            $table->dropIndex(['customer_id', 'id']);
            $table->dropIndex(['guest_key', 'id']);
            $table->dropConstrainedForeignId('customer_id');
        });
    }
};
