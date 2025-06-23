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
            $table->foreignId('brand_id')->nullable()->constrained('store_item_brands')->onDelete('set null');

            $table->string('item_code');
            $table->string('name');

            $table->unsignedBigInteger('measurement')->nullable();
            $table->foreign('measurement')->references('id')->on('measuring_units')->onDelete('set null')->onUpdate('cascade');

            $table->string('featured_image')->nullable();

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
        Schema::dropIfExists('store_items');
    }
};
