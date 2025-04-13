<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('billings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studentID')->constrained('students')->onDelete('cascade');
            $table->foreignId('courseID')->constrained('courses')->onDelete('cascade');
            $table->longText('receipt')->nullable();
            $table->string('paymentMethod');
            $table->string('paymentStatus');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('billings');
    }
};
