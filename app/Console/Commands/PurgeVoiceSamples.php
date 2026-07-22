<?php

namespace App\Console\Commands;

use App\Models\VoiceSample;
use App\Services\VoiceSampleStorage;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class PurgeVoiceSamples extends Command
{
    protected $signature = 'voice-samples:purge
                            {--before= : Delete recordings with recorded_at before this date (YYYY-MM-DD)}
                            {--dry-run : Count matching recordings without deleting}';

    protected $description = 'Delete voice recordings (DB + storage) recorded before a given date';

    public function handle(VoiceSampleStorage $storage): int
    {
        $before = $this->option('before');
        if (! is_string($before) || trim($before) === '') {
            $this->error('The --before=YYYY-MM-DD option is required.');

            return self::FAILURE;
        }

        try {
            $cutoff = Carbon::parse($before)->startOfDay();
        } catch (Throwable $e) {
            $this->error('Invalid --before date. Use YYYY-MM-DD.');

            return self::FAILURE;
        }

        $query = VoiceSample::query()->where('recorded_at', '<', $cutoff);
        $count = (clone $query)->count();

        if ($count === 0) {
            $this->info("No voice samples found with recorded_at before {$cutoff->toDateString()}.");

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("Dry run: {$count} voice sample(s) would be deleted (recorded_at < {$cutoff->toDateString()}).");

            return self::SUCCESS;
        }

        $deleted = 0;
        $failed = 0;

        $query->orderBy('id')->chunkById(100, function ($samples) use ($storage, &$deleted, &$failed) {
            foreach ($samples as $sample) {
                try {
                    $storage->delete($sample);
                    $deleted++;
                } catch (Throwable $e) {
                    $failed++;
                    $this->warn("Failed to delete voice sample #{$sample->id}: {$e->getMessage()}");
                    report($e);
                }
            }
        });

        $this->info("Deleted {$deleted} voice sample(s).");
        if ($failed > 0) {
            $this->warn("Failed to delete {$failed} voice sample(s).");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
