<?php

use App\Models\Organization;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('organizations', 'retain_voice_recordings')) {
            return;
        }

        Organization::query()
            ->where('slug', Organization::SUMMER_PRACTICE_PAL_SLUG)
            ->update(['retain_voice_recordings' => true]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('organizations', 'retain_voice_recordings')) {
            return;
        }

        Organization::query()
            ->where('slug', Organization::SUMMER_PRACTICE_PAL_SLUG)
            ->update(['retain_voice_recordings' => false]);
    }
};
