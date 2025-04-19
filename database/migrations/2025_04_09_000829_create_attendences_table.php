<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
    {
        Schema::create('attendences', function (Blueprint $table) {
            $table->id();  
            $table->foreignId('classID')->constrained('classes')->onDelete('cascade');
            $table->foreignId('studentID')->constrained('students')->onDelete('cascade');
            $table->date('date');
            $table->string('status');
            $table->timestamps();
        });
        
    }
    public function down(): void
    {
        Schema::dropIfExists('attendences');
    }
};
