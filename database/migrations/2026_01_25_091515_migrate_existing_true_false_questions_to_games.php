<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration creates True/False games for existing questions.
     * It groups questions by lesson_id and game_version, creating one game per group.
     */
    public function up(): void
    {
        // Get all unique lesson_id + game_version combinations
        $questionGroups = DB::table('true_false_questions')
            ->select('lesson_id', 'game_version')
            ->distinct()
            ->get();

        foreach ($questionGroups as $group) {
            // Create a game for this lesson + version combination
            $gameId = DB::table('true_false_games')->insertGetId([
                'lesson_id' => $group->lesson_id,
                'title' => 'True/False Game (' . ucfirst($group->game_version) . ')',
                'game_version' => $group->game_version,
                'is_active' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update all questions in this group to reference the new game
            DB::table('true_false_questions')
                ->where('lesson_id', $group->lesson_id)
                ->where('game_version', $group->game_version)
                ->update([
                    'true_false_game_id' => $gameId,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove game references from questions
        DB::table('true_false_questions')
            ->update(['true_false_game_id' => null]);
        
        // Note: We don't delete the games table here as that would be handled
        // by dropping the true_false_games table migration
    }
};
