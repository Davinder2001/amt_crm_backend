<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Main expenses table
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('heading');
            $table->text('description')->nullable();
            $table->string('file_path')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'paid', 'rejected'])->default('pending');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // Pivot: expense_item
        Schema::create('expense_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained('expenses')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('store_items')->onDelete('cascade');
            $table->timestamps();
        });

        // Pivot: expense_invoice
        Schema::create('expense_invoice', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained('expenses')->onDelete('cascade');
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->timestamps();
        });

        // Pivot: expense_user (optional)
        Schema::create('expense_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained('expenses')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_user');
        Schema::dropIfExists('expense_invoice');
        Schema::dropIfExists('expense_item');
        Schema::dropIfExists('expenses');
    }
};
