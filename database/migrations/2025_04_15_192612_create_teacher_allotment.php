<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teacher_allotment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courseID')->constrained('courses')->onDelete('cascade');
            $table->foreignId('teacherID')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('classTimingsID')->constrained('class_timings')->onDelete('cascade');
            $table->foreignId('studentID')->constrained('students')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_allotment');
    }
};
