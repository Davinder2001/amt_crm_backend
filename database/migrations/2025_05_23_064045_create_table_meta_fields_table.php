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
        Schema::create('table_meta_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('managed_table_id');
            $table->string('meta_key');
            $table->boolean('meta_value')->default(false);
            $table->timestamps();
            $table->foreign('managed_table_id')->references('id')->on('managed_tables')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_meta_fields');
    }
};
