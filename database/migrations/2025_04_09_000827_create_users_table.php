<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();  
            $table->foreignId('teacherID')->nullable()->constrained('teachers')->onDelete('cascade');
            $table->foreignId('studentID')->nullable()->constrained('students')->onDelete('cascade');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role'); 
            $table->timestamps();
        });
        
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
