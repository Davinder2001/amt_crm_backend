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
            $table->decimal('total_amount', 10, 2);
            $table->decimal('sub_total', 10, 2);

            // Service charge fields
            $table->decimal('service_charge_amount', 10, 2)->default(0);
            $table->decimal('service_charge_percent', 5, 2)->default(0);
            $table->decimal('service_charge_gst', 10, 2)->default(0);
            $table->decimal('service_charge_final', 10, 2)->default(0);

            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('delivery_charge', 5, 2)->default(0);
            $table->decimal('final_amount', 10, 2);

            $table->enum('payment_method', ['cash', 'online', 'card', 'credit'])->nullable();
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
