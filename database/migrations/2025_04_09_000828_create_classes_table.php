<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();  
            $table->foreignId('courseID')->constrained('courses')->onDelete('cascade');
            $table->foreignId('teacherID')->constrained('teachers')->onDelete('cascade');
            $table->unsignedBigInteger('enrollmentID')->nullable(); // optional, no FK provided
            $table->string('title');
            $table->text('description');
            $table->string('classLink');
            $table->integer('total_seats');
            $table->integer('filled_seats')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
