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
            // Type: 'image' or 'text'
            $table->string('option_type')->default('image')->after('label');
            
            // For text-based options (e.g., Hebrew translation)
            $table->string('option_text')->nullable()->after('option_type');
            
            // Audio file for just the word/label
            $table->string('word_audio_path')->nullable()->after('image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('options', function (Blueprint $table) {
            $table->dropColumn(['option_type', 'option_text', 'word_audio_path']);
        });
    }
};
