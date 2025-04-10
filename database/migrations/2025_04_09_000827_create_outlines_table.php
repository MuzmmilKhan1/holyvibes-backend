<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('outlines', function (Blueprint $table) {
            $table->id();  
            $table->foreignId('courseID')->constrained('courses')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outlines');
    }
};
