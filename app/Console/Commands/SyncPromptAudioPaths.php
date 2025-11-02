<?php

namespace App\Console\Commands;

use App\Models\Prompt;
use Illuminate\Console\Command;

class SyncPromptAudioPaths extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tts:sync-prompt-paths 
                            {--lesson= : Only sync for a specific lesson ID}
                            {--dry-run : Show what would be synced without updating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync prompt audio paths from existing files to database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Syncing prompt audio paths...');
        $this->newLine();

        $query = Prompt::all();

        if ($lessonId = $this->option('lesson')) {
            $query = $query->where('lesson_id', $lessonId);
        }

        $prompts = $query;

        if ($prompts->isEmpty()) {
            $this->warn('No prompts found.');
            return 0;
        }

        $this->info("Found {$prompts->count()} prompt(s) to check.");
        $this->newLine();

        $synced = 0;
        $missing = 0;
        $alreadyExists = 0;

        foreach ($prompts as $prompt) {
            // Skip if already has a path
            if (!empty($prompt->prompt_audio_path)) {
                $alreadyExists++;
                if ($this->output->isVerbose()) {
                    $this->info("  Prompt {$prompt->id}: Already has path: {$prompt->prompt_audio_path}");
                }
                continue;
            }

            // Check multiple possible locations for audio file
            $possiblePaths = [
                "tts/prompts/prompt_{$prompt->id}.mp3",
                "vocabulary-audio/prompt_{$prompt->id}.mp3",
                "tts/vocabulary-audio/prompt_{$prompt->id}.mp3",
            ];

            $foundPath = null;
            foreach ($possiblePaths as $expectedPath) {
                $fullPath = storage_path("app/public/{$expectedPath}");
                if (file_exists($fullPath)) {
                    $foundPath = $expectedPath;
                    break;
                }
            }

            if ($foundPath) {
                if (!$this->option('dry-run')) {
                    $prompt->update(['prompt_audio_path' => "/storage/{$foundPath}"]);
                }
                $this->line("  ✓ Synced prompt {$prompt->id}: {$prompt->prompt_text} (found at: {$foundPath})");
                $synced++;
            } else {
                $this->warn("  ✗ Missing audio for prompt {$prompt->id}: {$prompt->prompt_text}");
                if ($this->output->isVerbose()) {
                    foreach ($possiblePaths as $path) {
                        $this->line("    Checked: storage/app/public/{$path}");
                    }
                }
                $missing++;
            }
        }

        $this->newLine();
        $this->info('=== Summary ===');
        $this->info("Synced: {$synced} prompts");
        $this->info("Already had path: {$alreadyExists} prompts");
        $this->info("Missing audio: {$missing} prompts");

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->warn('This was a dry run. Run without --dry-run to actually sync.');
        }

        return 0;
    }
}

