<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('class_timings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classID')->nullable()->constrained('classes')->onDelete('cascade');
            $table->foreignId('teacherID')->nullable()->constrained('teachers')->onDelete('cascade');
            $table->foreignId('studentID')->nullable()->constrained('students')->onDelete('cascade');
            $table->foreignId('courseID')->nullable()->constrained('courses')->onDelete('cascade');
            $table->time('preferred_time_from');
            $table->time('preferred_time_to');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_timings');
    }
};
