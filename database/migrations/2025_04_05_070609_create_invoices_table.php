<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->string('client_name');
            $table->string('client_phone')->nullable();
            $table->string('client_email')->nullable();
            $table->date('invoice_date');

            // Use double for unlimited numeric size
            $table->double('total_amount');
            $table->double('sub_total');
            
            // Service charge fields
            $table->double('service_charge_amount')->default(0);
            $table->double('service_charge_percent')->default(0);
            $table->double('service_charge_gst')->default(0);
            $table->double('service_charge_final')->default(0);

            $table->double('discount_amount')->default(0);
            $table->double('discount_percentage')->default(0);
            
            $table->string('delivery_address')->nullable();
            $table->string('delivery_pincode')->nullable();
            $table->double('delivery_charge')->default(0);
            $table->double('final_amount');
            
            $table->enum('payment_method', ['cash', 'online', 'card', 'credit', 'self'])->nullable();
            $table->string('credit_note')->nullable();
            $table->foreignId('issued_by')->constrained('users')->onDelete('cascade');
            $table->string('issued_by_name')->nullable();
            $table->longText('pdf_base64')->nullable();
            $table->boolean('sent_on_whatsapp')->default(false);
            
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
}
