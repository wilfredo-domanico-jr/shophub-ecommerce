<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // FK drives per-customer usage counting; the code column is a
            // display snapshot that survives voucher deletion.
            $table->foreignId('voucher_id')->nullable()->after('user_id')
                ->constrained()->nullOnDelete();
            $table->string('voucher_code', 50)->nullable()->after('shipping_fee');
            $table->decimal('discount', 10, 2)->default(0)->after('voucher_code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voucher_id');
            $table->dropColumn(['voucher_code', 'discount']);
        });
    }
};
