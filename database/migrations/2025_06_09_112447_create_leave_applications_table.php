<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaveApplicationsTable extends Migration
{
    public function up(): void
    {
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('attendance_id')->nullable();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('leave_id')->constrained('leaves')->onDelete('cascade');
            $table->date('leave_date');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreign('attendance_id')->references('id')->on('attendances')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_applications');
    }
}
