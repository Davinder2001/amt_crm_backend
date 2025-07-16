<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendancesTable extends Migration
{
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('company_id');
            $table->date('attendance_date');
            $table->string('clock_in')->nullable();
            $table->string('clock_in_image')->nullable(); 
            $table->string('clock_out')->nullable();
            $table->string('clock_out_image')->nullable();
            $table->enum('status', ['present', 'absent', 'leave'])->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->unique(['user_id', 'attendance_date'], 'unique_daily_attendance');
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendances');
    }
}
