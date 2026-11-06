<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rintis_orders', function (Blueprint $table): void {
            $table->text('pickup_code')->nullable();
            $table->string('pickup_code_hash', 64)->nullable()->unique();
            $table->timestamp('collected_at')->nullable();
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('cash_received')->nullable();
            $table->unsignedBigInteger('cash_change')->nullable();
        });
        DB::table('rintis_orders')->where('method', 'pickup')->orderBy('id')->each(function ($order): void {
            $code = Str::upper(Str::random(16));
            DB::table('rintis_orders')->where('id', $order->id)->update(['pickup_code' => Crypt::encryptString($code), 'pickup_code_hash' => hash('sha256', $code)]);
        });
    }

    public function down(): void
    {
        Schema::table('rintis_orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('collected_by');
            $table->dropUnique(['pickup_code_hash']);
            $table->dropColumn(['pickup_code', 'pickup_code_hash', 'collected_at', 'cash_received', 'cash_change']);
        });
    }
};
