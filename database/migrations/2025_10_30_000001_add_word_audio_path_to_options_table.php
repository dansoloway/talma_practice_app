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
        Schema::table('options', function (Blueprint $table) {
            if (!Schema::hasColumn('options', 'word_audio_path')) {
                $table->string('word_audio_path')->nullable()->after('image_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('options', function (Blueprint $table) {
            if (Schema::hasColumn('options', 'word_audio_path')) {
                $table->dropColumn('word_audio_path');
            }
        });
    }
};


