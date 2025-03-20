<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyUserTable extends Migration
{
    public function up()
    {
        Schema::create('company_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->enum('user_type', ['super_admin', 'admin', 'staff'])->nullable();
            $table->boolean('status')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'company_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('company_user');
    }
}
