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

            $table->unsignedBigInteger('package_id')->nullable();
            $table->foreign('package_id')->references('id')->on('packages')->nullOnDelete();

            $table->unsignedBigInteger('business_category')->nullable();
            $table->foreign('business_category')->references('id')->on('business_categories')->nullOnDelete();

            $table->dateTime('subscription_date')->nullable();
            $table->enum('subscription_status', ['active', 'expired']);
            $table->enum('subscription_type', ['monthly', 'annual']);


            $table->string('company_name')->unique();
            $table->string('company_id')->unique();
            $table->string('company_logo')->nullable();
            $table->string('company_slug')->unique();

            $table->string('business_address')->nullable();
            $table->string('pin_code', 20)->nullable();
            $table->string('business_proof_type')->nullable();
            $table->string('business_id')->nullable();
            $table->string('business_proof_front')->nullable();
            $table->string('business_proof_back')->nullable();
            $table->string('order_id')->nullable();
            $table->string('transation_id')->nullable();
            $table->string('payment_recoad_status')->nullable();

            $table->enum('payment_status', ['pending', 'completed', 'failed'])->default('pending');
            $table->enum('verification_status', ['pending', 'verified', 'rejected', 'block'])->default('pending');

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
