<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run migrations
     */
    public function up(): void
    {
        Schema::create('variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->json('attributes')->nullable();

            $table->decimal('sell_price', 15, 0)->default(0);
            $table->decimal('base_price', 15, 2)->nullable();
            $table->decimal('sell_rate', 15, 1)->nullable();

            $table->decimal('buy_price', 15, 0)->default(0);
            $table->decimal('base_buy_price', 15, 2)->nullable();
            $table->decimal('buy_rate', 15, 1)->nullable();

            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('sold_count')->default(0);

            $table->decimal('cached_final_price', 10, 0)->nullable();
            $table->decimal('cached_profit', 10, 1)->nullable();
            $table->decimal('cached_profit_percentage', 10, 1)->nullable();
    
            $table->string('group_key')->nullable()->index();

            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('image')->nullable();
            $table->timestamps();

            // ⚡ HIGH-PERFORMANCE COMPOSITE INDEXES
            $table->index(['product_id', 'is_active', 'created_at']);
            $table->index('is_default');
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
