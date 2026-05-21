<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    

    public function up(): void
{
    Schema::create('variation_attributes', function (Blueprint $table) {

        $table->id();

        $table->foreignId('variation_id')
            ->constrained()
            ->cascadeOnDelete();

        $table->foreignId('attribute_id')
            ->constrained()
            ->cascadeOnDelete();

        $table->foreignId('attribute_option_id')
            ->constrained()
            ->cascadeOnDelete();
 
            
        $table->timestamps();

        // $table->unique([
        //     'variation_id',
        //     'attribute_id'
        // ], 'variation_attribute_unique');

        $table->index('variation_id');
        $table->index('attribute_id');
        $table->index('attribute_option_id');
    });
}


    public function down(): void
    {
        Schema::table('variation_attributes', function (Blueprint $table) {

            $table->dropUnique('variation_attribute_unique');

        });
    }
};
