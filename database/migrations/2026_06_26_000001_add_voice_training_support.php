<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('retain_voice_recordings')->default(false)->after('allow_self_registration');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('age')->nullable()->after('is_active');
            $table->string('gender', 10)->nullable()->after('age');
            $table->timestamp('voice_recording_consented_at')->nullable()->after('gender');
        });

        Schema::create('voice_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prompt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('option_id')->constrained()->cascadeOnDelete();
            $table->string('target_text');
            $table->unsignedSmallInteger('age');
            $table->string('gender', 10);
            $table->string('s3_key');
            $table->string('metadata_s3_key')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('mime_original', 50)->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['organization_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_samples');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['age', 'gender', 'voice_recording_consented_at']);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('retain_voice_recordings');
        });
    }
};
