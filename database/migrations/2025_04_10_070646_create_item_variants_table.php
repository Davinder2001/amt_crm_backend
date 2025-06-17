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
            
        Schema::create('item_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('store_items')->onDelete('cascade');
            $table->decimal('variant_regular_price', 10, 2)->nullable(); 
            $table->decimal('variant_sale_price', 10, 2)->nullable();
            $table->decimal('variant_units_in_peace', 10, 2)->nullable();
            $table->decimal('variant_price_per_unit', 10, 2)->nullable();
            $table->integer('stock')->nullable();
            $table->json('images')->nullable();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_variants');
    }
};
