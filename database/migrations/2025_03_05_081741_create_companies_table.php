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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->foreign('admin_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('company_name')->unique();
            $table->string('company_id')->unique();
            $table->string('company_slug')->unique();
            $table->string('business_address')->nullable();
            $table->string('pin_code', 20)->nullable();
            $table->string('business_proof_type')->nullable();
            $table->string('business_id')->nullable();
            $table->string('business_proof_front')->nullable();
            $table->string('business_proof_back')->nullable();
            $table->enum('payment_status', ['pending', 'completed', 'failed'])->default('pending');
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
        });
        Schema::dropIfExists('companies');
    }
};
