<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('item_batches', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->string('invoice_number')->nullable();

            $table->decimal('quantity', 20, 2)->nullable();

            $table->date('purchase_date')->nullable();
            $table->date('date_of_manufacture')->nullable();
            $table->date('date_of_expiry')->nullable();

            $table->string('replacement')->nullable();
            $table->enum('tax_type', ['include', 'exclude'])->default('exclude');


            $table->decimal('cost_price', 20, 2)->nullable();
            $table->decimal('regular_price', 20, 2)->nullable();
            $table->decimal('sale_price', 20, 2)->nullable();
            $table->decimal('units_in_peace')->nullable();
            $table->decimal('price_per_unit', 20, 2)->nullable();

            $table->string('product_type')->nullable();
            $table->string('unit_of_measure')->nullable();

            $table->timestamps();

            $table->foreign('vendor_id')->references('id')->on('store_vendors')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('store_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_batches');
    }
};
