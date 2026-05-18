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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('restrict');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->text('short_description')->nullable();
            $table->string('barcode')->nullable()->unique();
            $table->text('featured_image')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('view_count')->default(0);
            $table->integer('sold_count')->default(0);  
            $table->softDeletes();
            $table->timestamps();
            
          
            $table->index('slug');
            $table->index('category_id');
            $table->index('status');
            $table->index('is_featured');
            $table->index('is_active');
            $table->index('created_at');
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
