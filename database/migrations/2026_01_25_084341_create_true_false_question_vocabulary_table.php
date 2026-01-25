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
        Schema::create('true_false_question_vocabulary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('true_false_question_id')->constrained()->onDelete('cascade');
            $table->foreignId('vocabulary_id')->constrained('vocabulary')->onDelete('cascade');
            $table->timestamps();
            
            // Ensure each question-vocab pair is unique (shorter name for MySQL)
            $table->unique(['true_false_question_id', 'vocabulary_id'], 'tfq_vocab_unique');
            $table->index('vocabulary_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('true_false_question_vocabulary');
    }
};
