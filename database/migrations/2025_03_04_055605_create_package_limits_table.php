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
        Schema::create('package_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->onDelete('cascade');
            $table->enum('variant_type', ['monthly', 'annual', 'three_years']);

            $table->integer('employee_numbers')->nullable()->default(null);
            $table->integer('items_number')->nullable()->default(null);
            $table->integer('daily_tasks_number')->nullable()->default(null);
            $table->integer('invoices_number')->nullable()->default(null);

            $table->boolean('task')->default(false);
            $table->boolean('chat')->default(false);
            $table->boolean('hr')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_limits');
    }
};
