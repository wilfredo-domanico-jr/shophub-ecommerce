<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable()->after('payment_status');
            $table->string('stripe_session_id')->nullable()->after('paid_at');
            $table->string('stripe_payment_intent_id')->nullable()->after('stripe_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['paid_at', 'stripe_session_id', 'stripe_payment_intent_id']);
        });
    }
};
