<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('variations', function (Blueprint $table) {
        $table->id();

        $table->foreignId('product_id')
            ->constrained()
            ->cascadeOnDelete();

        // Real SKU
        $table->string('sku')->unique();

        // Main price
        $table->decimal('price', 10, 2);

        // Inventory
        $table->unsignedInteger('quantity')->default(0);

        // Analytics
        $table->unsignedInteger('sold_count')->default(0);

        // UI helpers
        $table->boolean('is_default')->default(false);

        // Optional preview image
        $table->string('image')->nullable();

        // Status
        $table->boolean('is_active')->default(true);

        $table->timestamps();

        $table->index('product_id');
        $table->index('sku');
        $table->index('is_default');
        $table->index('is_active');
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variations');
    }
};
