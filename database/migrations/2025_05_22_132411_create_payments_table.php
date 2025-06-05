<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('order_id');
            $table->unsignedBigInteger('user_id');
            $table->string('transaction_id')->unique();
            $table->string('payment_status');
            $table->string('payment_method')->nullable();
            $table->text('payment_fail_reason')->nullable();
            $table->text('payment_reason')->nullable();
            $table->string('transaction_amount')->nullable();
            $table->string('payment_date');
            $table->string('payment_time');
            $table->enum('refund', ['refunded', 'refund_approved',  'refund_processed', 'refund_declined'])->nullable();
            $table->text('refund_reason')->nullable();
            $table->text('decline_reason')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
