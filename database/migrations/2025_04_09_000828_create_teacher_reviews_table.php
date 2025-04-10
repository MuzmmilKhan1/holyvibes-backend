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
        Schema::create('teacher_reviews', function (Blueprint $table) {
            $table->id();  
            $table->foreignId('teacherID')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('studentID')->constrained('students')->onDelete('cascade');
            $table->text('review');
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_reviews');
    }
};
