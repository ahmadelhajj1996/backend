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
        Schema::create('variation_images', function (Blueprint $table) {

            $table->id();

            $table->foreignId('variation_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->text('path');

            // Sorting
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index('variation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variation_images');
    }
};
