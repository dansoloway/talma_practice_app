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
        Schema::create('true_false_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->onDelete('cascade');
            $table->text('statement'); // The True/False statement
            $table->boolean('is_true'); // Whether the statement is true or false
            $table->text('explanation'); // Explanation shown after answer
            $table->string('category')->nullable(); // science_facts, procedures, vocabulary, etc.
            $table->string('audio_path')->nullable(); // Path to TTS audio for statement
            $table->boolean('is_approved')->default(false); // Admin approval status
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['lesson_id', 'is_active']);
            $table->index('is_approved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('true_false_questions');
    }
};
