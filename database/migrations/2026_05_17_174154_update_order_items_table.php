<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {

            // $table->json('selected_attributes')->nullable();

            $table->dropColumn([
                'product_name',
                'variation_sku',
                'product_image',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
