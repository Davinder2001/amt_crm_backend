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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('mobile_no')->unique();
            $table->string('password');
            $table->string('aadhar_card_no')->unique();

            // Define 'role_id' column before adding the foreign key
            $table->unsignedBigInteger('role_id')->nullable();
            $table->foreign('role_id')->references('id')->on('emp_roles')->onDelete('set null');

            // Define 'admin_id' column before adding the foreign key
            $table->unsignedBigInteger('admin_id');
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['admin_id']); // Drop admin foreign key
            $table->dropForeign(['role_id']);  // Drop role foreign key
            $table->dropColumn('admin_id');
            $table->dropColumn('role_id');
        });

        Schema::dropIfExists('employees');
    }
};
