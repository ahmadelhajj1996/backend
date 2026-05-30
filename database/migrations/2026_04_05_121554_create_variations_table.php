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

            /*
            |--------------------------------------------------------------------------
            | Relationships
            |--------------------------------------------------------------------------
            */

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Identification
            |--------------------------------------------------------------------------
            */

            $table->string('sku')
                ->unique();

            /*
            |--------------------------------------------------------------------------
            | Selling Prices
            |--------------------------------------------------------------------------
            */

            // Local sell price (SYP)
            $table->decimal('sell_price', 15, 2)
                ->default(0);

            // USD sell price
            $table->decimal('base_price', 15, 2)
                ->nullable();

            // Exchange rate used for sell price
            $table->decimal('sell_rate', 15, 4)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Supplier / Cost Prices
            |--------------------------------------------------------------------------
            */

            // Local supplier cost (SYP)
            $table->decimal('buy_price', 15, 2)
                ->default(0);

            // USD supplier cost
            $table->decimal('base_buy_price', 15, 2)
                ->nullable();

            // Exchange rate used for supplier cost
            $table->decimal('buy_rate', 15, 4)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Inventory
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('quantity')
                ->default(0);

            $table->unsignedInteger('sold_count')
                ->default(0);

            $table->decimal('cached_final_price', 10, 2)
                ->nullable();

            $table->decimal('cached_profit', 10, 2)
                ->nullable();

            $table->decimal('cached_profit_percentage', 10, 2)
                ->nullable();



            $table->boolean('is_default')
                ->default(false);

            $table->boolean('is_active')
                ->default(true);

            $table->string('image')
                ->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('product_id');
            $table->index('sku');
            $table->index('is_default');
            $table->index('is_active');
        });
    }

    /**
     * Reverse migrations
     */
    public function down(): void
    {
        Schema::dropIfExists('variations');
    }
};
