<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('date_of_birth');
            $table->string('guardian_name');
            $table->string('email');
            $table->string('contact_number');
            $table->string('alternate_contact_number')->nullable();
            $table->string('preferred_language')->nullable();
            $table->string('signature')->nullable();
            $table->date('registration_date');
            $table->json('class_course_data')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};

