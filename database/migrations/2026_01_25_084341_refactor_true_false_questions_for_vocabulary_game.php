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
        Schema::table('true_false_questions', function (Blueprint $table) {
            // Remove grammar_set_id foreign key and column
            $table->dropForeign(['grammar_set_id']);
            $table->dropIndex(['grammar_set_id']);
            $table->dropColumn('grammar_set_id');
            
            // Add game_version enum
            $table->enum('game_version', ['easy', 'medium', 'hard'])->default('easy')->after('lesson_id');
            $table->index('game_version');
        });
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
