<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_enrollment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studentId')->constrained('students')->onDelete('cascade');
            $table->foreignId('classId')->constrained('classes')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['studentId', 'classId']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_enrollment');
    }
};
