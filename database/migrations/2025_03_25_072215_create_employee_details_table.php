<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_details', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->unique();

            $table->decimal('salary', 10, 2)->nullable();

            $table->date('dateOfHire')->nullable();
            $table->date('joiningDate')->nullable();
            $table->unsignedBigInteger('shift_id')->nullable();

            $table->text('address')->nullable();
            $table->string('nationality', 30)->nullable();
            $table->date('dob')->nullable();
            $table->string('religion', 30)->nullable();
            $table->string('maritalStatus', 20)->nullable();

            $table->string('idProofType', 100)->nullable();
            $table->string('idProofValue', 100)->nullable();
            $table->string('id_proof_type', 50)->nullable();

            $table->unsignedBigInteger('emergencyContact')->nullable(); // ✅ numeric
            $table->unsignedBigInteger('emergencyContactRelation')->nullable(); // ✅ numeric

            $table->string('workLocation', 100)->nullable();
            $table->enum('joiningType', ['full-time', 'part-time', 'contract']);

            $table->string('department', 50)->nullable();
            $table->string('previousEmployer', 50)->nullable();

            $table->string('acc_hol_name', 100)->nullable();
            $table->string('bankName', 50)->nullable();
            $table->unsignedBigInteger('accountNo')->nullable();
            $table->string('ifscCode', 11)->nullable();
            $table->string('panNo', 10)->nullable();
            $table->string('upiId', 50)->nullable();
            $table->string('addressProof', 50)->nullable();

            $table->string('profilePicture')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_details');
    }
};
