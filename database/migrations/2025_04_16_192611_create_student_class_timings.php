<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_class_timings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classID')->nullable()->constrained('classes');
            $table->foreignId('studentID')->constrained('students')->onDelete('cascade');
            $table->foreignId('courseID')->constrained('courses')->onDelete('cascade');
            $table->foreignId('allotmentID')->constrained('teacher_allotment')->onDelete('cascade');
            $table->time('preferred_time_from');
            $table->time('preferred_time_to');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('student_class_timings');
    }
};
