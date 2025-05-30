<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->json('items'); 
            $table->unsignedBigInteger('invoice_id');
            $table->date('purchase_date');
            $table->string('invoice_no')->nullable();
            $table->decimal('subtotal', 10, 2); 

            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_histories');
    }
};
