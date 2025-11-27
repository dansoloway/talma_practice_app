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
        Schema::create('sentence_builder_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sentence_builder_game_id')->constrained('sentence_builder_games')->onDelete('cascade');
            $table->json('correct_sentence'); // Array of words in correct order
            $table->json('word_options'); // Array of all words (correct + distractors)
            $table->text('explanation'); // Simple explanation
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sentence_builder_questions');
    }
};
