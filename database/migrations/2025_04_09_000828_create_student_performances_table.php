<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_performances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courseID')->constrained('courses')->onDelete('cascade');
            $table->foreignId('studentID')->constrained('students')->onDelete('cascade');
            $table->foreignId('teacherID')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('classID')->constrained('classes')->onDelete('cascade');
            
            $table->string('attendance');
            $table->text('test_remarks');
            $table->text('participation');
            $table->text('suggestions');
            $table->timestamps();
            
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_performances');
    }
};
