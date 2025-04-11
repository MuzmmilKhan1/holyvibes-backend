<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_enrollment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studentId')->constrained('students')->onDelete('cascade');
            $table->foreignId('courseId')->constrained('courses')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['studentId', 'courseId']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_enrollment');
    }
};
