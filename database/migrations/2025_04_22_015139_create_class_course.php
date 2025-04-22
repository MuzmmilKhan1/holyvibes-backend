<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('class_course', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classID')->constrained('classes')->onDelete('cascade');
            $table->foreignId('courseID')->constrained('courses')->onDelete('cascade');
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('class_course');
    }
};
