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
        Schema::create('emp_roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('role_name');

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
        Schema::table('emp_roles', function (Blueprint $table) {
            $table->dropForeign(['admin_id']); // Drop foreign key first
            $table->dropColumn('admin_id');
        });

        Schema::dropIfExists('emp_roles');
    }
};
