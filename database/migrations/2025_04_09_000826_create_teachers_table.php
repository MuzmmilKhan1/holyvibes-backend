<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('date_of_birth');
            $table->string('gender');
            $table->string('nationality');
            $table->string('contact_number');
            $table->string('email');
            $table->text('current_address');
            $table->text('experience_Quran');
            $table->text('other_experience');
            $table->enum('languages_spoken', ["urdu", "english", "arabic",]);
            $table->enum('age_group', ['children', 'teenagers', 'adults']);
            $table->string('qualification');
            $table->string('institution');
            $table->date('application_date');
            $table->enum('status', ['pending', 'blocked', 'allowed'])->default('pending');
            $table->json('class_course_schedule')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
