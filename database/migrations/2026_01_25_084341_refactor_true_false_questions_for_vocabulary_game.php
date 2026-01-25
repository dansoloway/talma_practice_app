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
        // Remove grammar_set_id if it exists (from earlier migration)
        if (Schema::hasColumn('true_false_questions', 'grammar_set_id')) {
            Schema::table('true_false_questions', function (Blueprint $table) {
                // Try to drop foreign key (may not exist on fresh installs)
                try {
                    $table->dropForeign(['grammar_set_id']);
                } catch (\Exception $e) {
                    // Foreign key doesn't exist, continue
                }
                
                // Drop index and column
                try {
                    $table->dropIndex(['grammar_set_id']);
                } catch (\Exception $e) {
                    // Index doesn't exist, continue
                }
                
                $table->dropColumn('grammar_set_id');
            });
        }
        
        // Add game_version enum if it doesn't exist
        if (!Schema::hasColumn('true_false_questions', 'game_version')) {
            Schema::table('true_false_questions', function (Blueprint $table) {
                $table->enum('game_version', ['easy', 'medium', 'hard'])->default('easy')->after('lesson_id');
                $table->index('game_version');
            });
        }
        
        // Set default game_version for any existing questions that might not have it
        \DB::table('true_false_questions')
            ->whereNull('game_version')
            ->update(['game_version' => 'easy']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('true_false_questions', function (Blueprint $table) {
            // Restore grammar_set_id
            $table->foreignId('grammar_set_id')->nullable()->after('lesson_id')->constrained()->onDelete('set null');
            $table->index('grammar_set_id');
            
            // Remove game_version
            $table->dropIndex(['game_version']);
            $table->dropColumn('game_version');
        });
    }
};
