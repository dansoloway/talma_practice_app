<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Models\Option;
use App\Services\Tts\OptionSentenceAudioResolver;
use Illuminate\Console\Command;

class SyncSentenceAudioPaths extends Command
{
    protected $signature = 'tts:sync-sentence-paths
                            {--lesson= : Only sync options for a specific lesson ID}';

    protected $description = 'Backfill options.sentence_audio_path from legacy prompt_option_assets and existing files';

    public function handle(OptionSentenceAudioResolver $resolver): int
    {
        $query = Option::query()->with('prompt');

        if ($lessonId = $this->option('lesson')) {
            $query->whereHas('prompt', fn ($q) => $q->where('lesson_id', $lessonId));
        }

        $updated = 0;
        $alreadySet = 0;
        $noAudioFound = 0;

        $query->chunkById(100, function ($options) use ($resolver, &$updated, &$alreadySet, &$noAudioFound) {
            foreach ($options as $option) {
                if (filled($option->sentence_audio_path)) {
                    $alreadySet++;

                    continue;
                }

                $resolvedPath = $resolver->resolveRelativePath($option);
                if (! $resolvedPath) {
                    $noAudioFound++;

                    continue;
                }

                $option->update(['sentence_audio_path' => $resolvedPath]);
                $updated++;
            }
        });

        $this->info("Updated {$updated} option(s).");
        $this->line("  Already had sentence_audio_path: {$alreadySet}");
        $this->line("  No audio file found to sync: {$noAudioFound}");

        if ($lessonId = $this->option('lesson')) {
            $lesson = Lesson::find($lessonId);
            if ($lesson) {
                $remaining = Option::query()
                    ->whereHas('prompt', fn ($q) => $q->where('lesson_id', $lessonId))
                    ->whereNull('sentence_audio_path')
                    ->count();
                $this->comment("Lesson {$lesson->id} ({$lesson->title}): {$remaining} option(s) still missing sentence audio.");

                if ($remaining > 0 && $noAudioFound > 0) {
                    $this->comment('Generate missing audio with: php artisan tts:generate-prompt-audio --lesson='.$lessonId.' --sentences');
                }
            }
        }

        return 0;
    }
}
