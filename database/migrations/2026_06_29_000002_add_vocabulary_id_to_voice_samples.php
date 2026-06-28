<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('voice_samples', 'vocabulary_id')) {
            Schema::table('voice_samples', function (Blueprint $table) {
                $table->foreignId('vocabulary_id')->nullable()->after('lesson_id')
                    ->constrained('vocabulary')->nullOnDelete();
            });
        }

        Schema::table('voice_samples', function (Blueprint $table) {
            $table->dropForeign(['prompt_id']);
            $table->dropForeign(['option_id']);
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE voice_samples MODIFY prompt_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE voice_samples MODIFY option_id BIGINT UNSIGNED NULL');
        } elseif ($driver === 'sqlite') {
            // SQLite tests: recreate table with nullable prompt/option columns
            DB::statement('PRAGMA foreign_keys=OFF');
            DB::statement('CREATE TABLE voice_samples_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                organization_id INTEGER NOT NULL,
                lesson_id INTEGER NOT NULL,
                vocabulary_id INTEGER NULL,
                prompt_id INTEGER NULL,
                option_id INTEGER NULL,
                target_text VARCHAR NOT NULL,
                age INTEGER NOT NULL,
                gender VARCHAR NOT NULL,
                native_language VARCHAR,
                s3_key VARCHAR NOT NULL,
                metadata_s3_key VARCHAR,
                duration_ms INTEGER,
                mime_original VARCHAR,
                recorded_at DATETIME NOT NULL,
                created_at DATETIME,
                updated_at DATETIME,
                FOREIGN KEY(organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
                FOREIGN KEY(lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
                FOREIGN KEY(vocabulary_id) REFERENCES vocabulary(id) ON DELETE SET NULL,
                FOREIGN KEY(prompt_id) REFERENCES prompts(id) ON DELETE SET NULL,
                FOREIGN KEY(option_id) REFERENCES options(id) ON DELETE SET NULL
            )');
            DB::statement('INSERT INTO voice_samples_new SELECT id, organization_id, lesson_id, vocabulary_id, prompt_id, option_id, target_text, age, gender, native_language, s3_key, metadata_s3_key, duration_ms, mime_original, recorded_at, created_at, updated_at FROM voice_samples');
            DB::statement('DROP TABLE voice_samples');
            DB::statement('ALTER TABLE voice_samples_new RENAME TO voice_samples');
            DB::statement('PRAGMA foreign_keys=ON');
        }

        if ($driver === 'mysql') {
            Schema::table('voice_samples', function (Blueprint $table) {
                $table->foreign('prompt_id')->references('id')->on('prompts')->nullOnDelete();
                $table->foreign('option_id')->references('id')->on('options')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('voice_samples', function (Blueprint $table) {
            $table->dropForeign(['vocabulary_id']);
            $table->dropColumn('vocabulary_id');
        });
    }
};
