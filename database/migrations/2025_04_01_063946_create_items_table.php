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
        Schema::create('store_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('item_code');
            $table->string('name'); 
            $table->integer('quantity_count');
            $table->string('measurement')->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('date_of_manufacture');
            $table->date('date_of_expiry')->nullable();
            $table->string('brand_name');
            $table->string('replacement')->nullable();
            $table->string('category')->nullable();
            $table->string('vendor_name')->nullable();
            $table->integer('availability_stock')->default(0);
            $table->json('images')->nullable();
            $table->boolean('catalog')->default(false);
            $table->boolean('online_visibility')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
