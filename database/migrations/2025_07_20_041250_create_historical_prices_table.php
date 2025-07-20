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
        Schema::create('historical_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->date('price_date');
            $table->decimal('pettah_wholesale', 10, 2)->nullable();
            $table->decimal('narahenpita_retail', 10, 2)->nullable();
            $table->string('currency', 3)->default('LKR');
            $table->timestamps();
            $table->unique(['product_id', 'price_date']);   // prevents duplicates
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historical_prices');
    }
};
