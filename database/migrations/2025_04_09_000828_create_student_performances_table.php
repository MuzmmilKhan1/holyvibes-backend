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
        Schema::create('student_performances', function (Blueprint $table) {
            $table->id();  
            $table->foreignId('courseID')->constrained('courses')->onDelete('cascade');
            $table->foreignId('studentID')->constrained('students')->onDelete('cascade');
            $table->foreignId('teacherID')->constrained('teachers')->onDelete('cascade');
            $table->string('assignment_title');
            $table->text('assignment_description')->nullable();
            $table->date('assignment_submissionDate')->nullable();
            $table->string('assignment_file')->nullable();
            $table->string('test_title')->nullable();
            $table->integer('test_total_marks')->nullable();
            $table->integer('test_obtained_marks')->nullable();
            $table->integer('co_curricular_score')->nullable();
            $table->integer('quiz_score')->nullable();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_performances');
    }
};
