<?php

use App\Models\Organization;
use App\Services\Import\SummerVocabAssetArchiver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('organizations')
            ->where('slug', Organization::SUMMER_PRACTICE_PAL_SLUG)
            ->update([
                'name' => 'TALMA Summer',
                'description' => 'TALMA Summer — login required for CEFR practice courses',
            ]);

        foreach (SummerVocabAssetArchiver::COURSE_SLUGS as $cefr => $slug) {
            DB::table('courses')
                ->where('slug', $slug)
                ->update([
                    'title' => "TALMA Summer — {$cefr}",
                    'description' => "TALMA Summer content for {$cefr} learners.",
                ]);
        }
    }

    public function down(): void
    {
        DB::table('organizations')
            ->where('slug', Organization::SUMMER_PRACTICE_PAL_SLUG)
            ->update([
                'name' => 'Summer Practice Pal',
                'description' => 'Summer Practice Pal — login required for CEFR practice courses',
            ]);

        foreach (SummerVocabAssetArchiver::COURSE_SLUGS as $cefr => $slug) {
            DB::table('courses')
                ->where('slug', $slug)
                ->update([
                    'title' => "Summer Practice Pal — {$cefr}",
                    'description' => "Summer Practice Pal content for {$cefr} learners.",
                ]);
        }
    }
};
