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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('employee_numbers');
            $table->unsignedInteger('items_number');
            $table->unsignedInteger('daily_tasks_number');
            $table->unsignedInteger('invoices_number');
            $table->decimal('monthly_price', 10, 2);
            $table->decimal('annual_price', 10, 2);
            $table->unsignedBigInteger('business_category_id')->nullable();
            $table->foreign('business_category_id')->references('id')->on('business_categories')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.f
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
