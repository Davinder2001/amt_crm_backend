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
            $table->timestamp('clock_in')->nullable();
            $table->string('clock_in_image')->nullable(); // Added column for clock in image
            $table->timestamp('clock_out')->nullable();
            $table->string('clock_out_image')->nullable(); // Added column for clock out image
            $table->string('status')->nullable();
            $table->timestamps();

            // Add foreign keys and unique index for daily records
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
