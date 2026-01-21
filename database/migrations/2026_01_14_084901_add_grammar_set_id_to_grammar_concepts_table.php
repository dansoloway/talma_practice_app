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
        Schema::table('grammar_concepts', function (Blueprint $table) {
            $table->foreignId('grammar_set_id')->nullable()->after('id')->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grammar_concepts', function (Blueprint $table) {
            $table->dropForeign(['grammar_set_id']);
            $table->dropColumn('grammar_set_id');
        });
    }
};
