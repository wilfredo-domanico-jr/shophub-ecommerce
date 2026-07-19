<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Ordered option definitions, e.g.
            // [{"name":"Color","values":["Red","Blue"]},{"name":"Size","values":["S","M","L"]}]
            $table->json('options')->nullable()->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('options');
        });
    }
};
