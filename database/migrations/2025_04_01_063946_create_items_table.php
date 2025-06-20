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
            /*----------------------------------------------------------
            | Primary / FK
            *----------------------------------------------------------*/
            $table->id();

            $table
                ->foreignId('company_id')
                ->constrained('companies')
                ->onDelete('cascade');

            /* linked invoice, brand, vendor */
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();

            /*----------------------------------------------------------
            | Core item data
            *----------------------------------------------------------*/
            $table->string('item_code');
            $table->string('name');
            $table->integer('quantity_count');

            $table->unsignedBigInteger('measurement')->nullable();
            $table->foreign('measurement')->references('id')->on('measuring_units')->onDelete('set null')->onUpdate('cascade');


            $table->enum('unit_of_measure', ['pieces', 'unit'])->nullable();

            /* dates */
            $table->date('purchase_date')->nullable();
            $table->date('date_of_manufacture')->nullable();
            $table->date('date_of_expiry')->nullable();

            /* brand / product / sales meta */
            $table->string('brand_name')->nullable();
            $table->enum('product_type', ['simple_product', 'variable_product'])
                ->default('simple_product');
            $table->string('sale_type')->nullable();        // NEW

            /* misc text fields */
            $table->string('replacement')->nullable();
            $table->string('category')->nullable();         // legacy free-text category
            $table->string('vendor_name')->nullable();
            $table->string('featured_image')->nullable();

            /* stock & visibility */
            $table->integer('availability_stock')->default(0);
            $table->json('images')->nullable();
            $table->boolean('catalog')->default(false);
            $table->boolean('online_visibility')->default(false);

            /* pricing */
            $table->decimal('cost_price',    10, 2)->nullable();
            $table->decimal('regular_price', 10, 2)->nullable();
            $table->decimal('sale_price',    10, 2)->nullable();
            $table->decimal('units_in_peace',    10, 2)->nullable();
            $table->decimal('price_per_unit',    10, 2)->nullable();

            $table->timestamps();

            /*----------------------------------------------------------
            | Foreign-key constraints
            *----------------------------------------------------------*/
            $table->foreign('invoice_id')->references('id')->on('vendor_invoices')->onDelete('set null');
            $table->foreign('brand_id')->references('id')->on('store_item_brands')->onDelete('set null');
            $table->foreign('vendor_id')->references('id')->on('store_vendors')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_items');
    }
};
