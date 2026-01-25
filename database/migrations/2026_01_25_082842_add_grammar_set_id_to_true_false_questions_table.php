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
            $table->foreignId('grammar_set_id')->nullable()->after('lesson_id')->constrained()->onDelete('set null');
            $table->index('grammar_set_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('true_false_questions', function (Blueprint $table) {
            $table->dropForeign(['grammar_set_id']);
            $table->dropIndex(['grammar_set_id']);
            $table->dropColumn('grammar_set_id');
        });
    }
};
