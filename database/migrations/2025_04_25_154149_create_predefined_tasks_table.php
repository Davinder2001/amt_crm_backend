<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePredefinedTasksTable extends Migration
{
    public function up()
    {
        Schema::create('predefined_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('assigned_by');
            $table->unsignedBigInteger('assigned_to');
            $table->unsignedBigInteger('company_id');
            $table->string('assigned_role')->nullable();
            $table->enum('recurrence_type', ['daily', 'weekly', 'monthly']);
            $table->json('recurrence_days')->nullable(); // only for weekly
            $table->date('recurrence_start_date');
            $table->date('recurrence_end_date')->nullable();
            $table->boolean('notify')->default(true);
            $table->timestamps();

            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('predefined_tasks');
    }
}
