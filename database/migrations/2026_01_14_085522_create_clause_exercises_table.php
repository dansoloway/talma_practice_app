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
        Schema::create('clause_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->onDelete('cascade');
            $table->foreignId('grammar_set_id')->nullable()->constrained()->onDelete('set null');
            $table->string('title');
            $table->text('paragraph_text'); // The paragraph with blanks marked as {}
            $table->json('correct_answers'); // {"blank_1": "vocabulary_id", "blank_2": "vocabulary_id"}
            $table->json('blank_positions'); // [{"id": "blank_1", "position": 12, "vocabulary_id": 5}, ...]
            $table->enum('difficulty_level', ['easy', 'medium', 'hard'])->default('medium');
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
        Schema::dropIfExists('clause_exercises');
    }
};
