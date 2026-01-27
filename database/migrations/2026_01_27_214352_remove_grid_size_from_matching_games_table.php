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
        Schema::table('matching_games', function (Blueprint $table) {
            $table->dropColumn('grid_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matching_games', function (Blueprint $table) {
            $table->integer('grid_size')->default(4)->after('vocabulary_ids');
        });
    }
};
