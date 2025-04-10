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
        Schema::create('billings', function (Blueprint $table) {
            $table->id();  
            $table->foreignId('studentID')->constrained('students')->onDelete('cascade');
            $table->foreignId('courseID')->constrained('courses')->onDelete('cascade');
            $table->string('receipt')->nullable(); // image path
            $table->string('paymentMethod');
            $table->string('paymentStatus');
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billings');
    }
};
