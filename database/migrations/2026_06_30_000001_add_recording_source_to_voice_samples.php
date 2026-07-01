<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voice_samples', function (Blueprint $table) {
            $table->string('recording_source', 32)->default('manual_record')->after('mime_original');
        });
    }

    public function down(): void
    {
        Schema::table('voice_samples', function (Blueprint $table) {
            $table->dropColumn('recording_source');
        });
    }
};
