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
            // Add true_false_game_id (nullable for backward compatibility)
            $table->foreignId('true_false_game_id')->nullable()->after('lesson_id')->constrained()->onDelete('cascade');
            $table->index('true_false_game_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('true_false_questions', function (Blueprint $table) {
            $table->dropForeign(['true_false_game_id']);
            $table->dropIndex(['true_false_game_id']);
            $table->dropColumn('true_false_game_id');
        });
    }
};
