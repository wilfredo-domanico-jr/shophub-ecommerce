<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->json('option_values'); // {"Color":"Red","Size":"M"}
            // Deterministic key ("color=red|size=m") because a JSON column
            // can't back a portable unique index (tests run on SQLite).
            $table->string('variant_key');
            $table->decimal('price', 10, 2)->nullable(); // null => inherit product price
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->string('image')->nullable(); // null => inherit product image
            $table->timestamps();

            $table->unique(['product_id', 'variant_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
