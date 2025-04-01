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
        Schema::create('employee_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->decimal('salary', 10, 2)->nullable();
            $table->date('dateOfHire')->nullable();
            $table->date('joiningDate')->nullable();
            $table->string('shiftTimings')->nullable();
            $table->string('address')->nullable();
            $table->string('nationality')->nullable();
            $table->string('dob')->nullable();
            $table->string('religion')->nullable();
            $table->string('maritalStatus')->nullable();
            $table->string('passportNo')->nullable();
            $table->string('emergencyContact')->nullable();
            $table->string('emergencyContactRelation')->nullable();
            $table->string('currentSalary')->nullable();
            $table->string('workLocation')->nullable();
            $table->enum('joiningType', ['full-time', 'part-time', 'Contract']);
            $table->string('department')->nullable();
            $table->string('previousEmployer')->nullable();
            $table->string('medicalInfo')->nullable();
            $table->string('bankName')->nullable();
            $table->string('accountNo')->nullable();
            $table->string('ifscCode')->nullable();
            $table->string('panNo')->nullable();
            $table->string('upiId')->nullable();
            $table->string('addressProof')->nullable();
            $table->string('profilePicture')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
       
    }
 
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_details');
    }
};