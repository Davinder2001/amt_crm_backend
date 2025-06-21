<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('heading');
            $table->text('description')->nullable();
            $table->string('file_path')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'paid', 'rejected' ])->default('pending');
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
