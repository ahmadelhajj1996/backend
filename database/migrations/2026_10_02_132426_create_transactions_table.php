<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            // "income" (money in) or "expense" (money out). Amount is always positive.
            $table->string('type', 10);

            // Integer minor units (1250.50 => 125050). Never use float for money.
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3)->default('AED');

            $table->string('description');
            $table->text('notes')->nullable();

            $table->dateTime('occurred_at');

            $table->timestamps();
            $table->softDeletes(); // financial records are never hard-deleted

            $table->index('occurred_at');
            $table->index(['type', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};