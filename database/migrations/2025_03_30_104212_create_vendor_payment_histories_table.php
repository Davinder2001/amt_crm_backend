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
        Schema::create('vendor_payment_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_invoice_id');
            $table->string('payment_method')->nullable();
            $table->string('credit_payment_type')->nullable();
            $table->integer('partial_amount')->nullable();
            $table->decimal('amount_paid', 10, 2);
            $table->date('payment_date')->default(now());
            $table->string('note')->nullable();
            $table->timestamps();

            $table->foreign('vendor_invoice_id')->references('id')->on('vendor_invoices')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_payment_histories');
    }
};
