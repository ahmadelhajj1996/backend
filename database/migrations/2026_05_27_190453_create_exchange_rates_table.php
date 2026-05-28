<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('base_currency');   // USD
            $table->string('target_currency'); // SYP

            $table->decimal('rate', 20, 4);

            $table->decimal('previous_rate', 20, 4)
                ->nullable();

            $table->decimal('change_amount', 20, 4)
                ->nullable();

            $table->decimal('change_percentage', 10, 2)
                ->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
