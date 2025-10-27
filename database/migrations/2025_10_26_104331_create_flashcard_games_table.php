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
        Schema::create('flashcard_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->onDelete('cascade');
            $table->foreignId('part_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('title');
            $table->json('game_types'); // Array of game types: ['image_to_word', 'image_to_audio', 'audio_to_image', 'audio_to_word']
            $table->json('vocabulary_ids'); // Array of vocabulary IDs to use
            $table->integer('cards_per_game')->default(10); // How many cards per game
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
        Schema::dropIfExists('flashcard_games');
    }
};
