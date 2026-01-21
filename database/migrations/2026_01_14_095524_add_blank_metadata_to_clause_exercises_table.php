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
        Schema::table('clause_exercises', function (Blueprint $table) {
            $table->json('blank_metadata')->nullable()->after('blank_positions');
            // This will store: {"blank_1": {"type": "vocab", "correct_answer": 123, "distractors": [124, 125, 126]}, "blank_2": {"type": "grammar", "correct_answer": "should", "distractors": ["should not", "cannot", "will"], "grammar_concept_id": 5}}
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clause_exercises', function (Blueprint $table) {
            $table->dropColumn('blank_metadata');
        });
    }
};
