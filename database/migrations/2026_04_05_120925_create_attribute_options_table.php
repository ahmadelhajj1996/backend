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
        Schema::create('attribute_options', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attribute_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('value');

            $table->string('color_code')->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            $table->decimal('price_modifier', 10, 2)->default(0);

            $table->timestamps();

            $table->index('attribute_id');
            $table->unique(['attribute_id', 'value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_options');
    }
};
