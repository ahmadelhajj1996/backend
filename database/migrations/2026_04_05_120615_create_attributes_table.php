<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['text', 'select',  'boolean'])->default('select');
            $table->boolean('is_filterable')->default(true);
            $table->boolean('is_required')->default(false);  
            $table->string('sort_order')->default('asc');
            $table->softDeletes();
            $table->timestamps();
            $table->index('slug');
            $table->index('is_filterable');
            $table->index('type');
        });
    }

    public function down()
    {
        Schema::dropIfExists('attributes');
    }
};