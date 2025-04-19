<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('event_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eventID')->constrained('events')->onDelete('cascade');
            $table->foreignId('studentID')->constrained('users')->onDelete('cascade');
            $table->boolean('is_member')->default(false);
            $table->enum('payment_status', ['pending', 'paid', 'rejected', 'not_required'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_participants');
    }
};