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
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('grade_level', 20)->nullable()->after('instructions');
            $table->integer('session_number')->nullable()->after('grade_level');
            $table->string('session_title', 255)->nullable()->after('session_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['grade_level', 'session_number', 'session_title']);
        });
    }
};
