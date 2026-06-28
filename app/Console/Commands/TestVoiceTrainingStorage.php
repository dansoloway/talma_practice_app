<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JsonException;

class TestVoiceTrainingStorage extends Command
{
    protected $signature = 'voice-training:test-storage
                            {--keep : Leave the test objects in storage instead of deleting them}';

    protected $description = 'Verify voice training storage (local or S3) can write, read, and delete';

    public function handle(): int
    {
        $diskName = config('filesystems.voice_training_disk', 'voice_training');
        $diskConfig = config("filesystems.disks.{$diskName}");
        $driver = $diskConfig['driver'] ?? 'unknown';

        $this->info('Voice training storage connectivity test');
        $this->table(['Setting', 'Value'], [
            ['Disk', $diskName],
            ['Driver', $driver],
            ['Bucket / root', $driver === 's3' ? ($diskConfig['bucket'] ?? '(missing)') : ($diskConfig['root'] ?? '(missing)')],
            ['Region', $driver === 's3' ? ($diskConfig['region'] ?? '(missing)') : 'n/a'],
        ]);

        if ($driver === 's3') {
            $missing = array_filter([
                empty($diskConfig['key']) ? 'AWS_ACCESS_KEY_ID' : null,
                empty($diskConfig['secret']) ? 'AWS_SECRET_ACCESS_KEY' : null,
                empty($diskConfig['region']) ? 'AWS_DEFAULT_REGION' : null,
                empty($diskConfig['bucket']) ? 'AWS_VOICE_TRAINING_BUCKET' : null,
            ]);

            if ($missing) {
                $this->error('Missing required .env values: ' . implode(', ', $missing));

                return self::FAILURE;
            }

            if (! class_exists(\League\Flysystem\AwsS3V3\AwsS3V3Adapter::class)) {
                $this->error('S3 packages not installed. Run:');
                $this->line('  composer require league/flysystem-aws-s3-v3 "^3.0" aws/aws-sdk-php');

                return self::FAILURE;
            }
        }

        $testId = now()->format('Y-m-d_His') . '_' . Str::random(6);
        $jsonKey = "voice-training/_connectivity-test/{$testId}.json";
        $mp3Key = "voice-training/_connectivity-test/{$testId}.mp3";

        $payload = [
            'test' => true,
            'app' => config('app.name'),
            'timestamp' => now()->toIso8601String(),
            'bucket' => $diskConfig['bucket'] ?? null,
        ];

        $jsonBody = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $disk = Storage::disk($diskName);

        try {
            $this->components->task('Write JSON sidecar', function () use ($disk, $jsonKey, $jsonBody) {
                $written = $disk->put($jsonKey, $jsonBody, ['ContentType' => 'application/json']);

                if ($written === false) {
                    throw new \RuntimeException(
                        'put() returned false. Upload failed — check IAM policy, bucket name/region, and that the access key belongs to the same AWS account as the bucket.'
                    );
                }

                if (! $disk->exists($jsonKey)) {
                    throw new \RuntimeException(
                        "Upload did not create s3://…/{$jsonKey}. The access key may belong to a different AWS account than the bucket, or PutObject is denied."
                    );
                }

                return true;
            });

            $this->components->task('Read JSON sidecar', function () use ($disk, $jsonKey, $payload, $driver) {
                if (! $disk->exists($jsonKey)) {
                    throw new \RuntimeException("Object not found after upload: {$jsonKey}");
                }

                $size = $disk->size($jsonKey);
                if ($size === 0) {
                    throw new \RuntimeException('Object exists but size is 0 bytes.');
                }

                $raw = $disk->get($jsonKey);

                try {
                    $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $e) {
                    throw new \RuntimeException($this->describeUnreadableBody($raw, $driver, $e->getMessage()));
                }

                if (($decoded['test'] ?? false) !== true) {
                    throw new \RuntimeException('Unexpected JSON payload.');
                }

                if (($decoded['timestamp'] ?? null) !== $payload['timestamp']) {
                    throw new \RuntimeException('JSON round-trip mismatch.');
                }

                return true;
            });

            $this->components->task('Write MP3 placeholder', function () use ($disk, $mp3Key) {
                $written = $disk->put($mp3Key, "ID3\x03\x00connectivity-test", ['ContentType' => 'audio/mpeg']);

                if ($written === false || ! $disk->exists($mp3Key)) {
                    throw new \RuntimeException('MP3 placeholder upload failed.');
                }

                return true;
            });

            $this->components->task('Verify objects exist', function () use ($disk, $jsonKey, $mp3Key) {
                if (! $disk->exists($jsonKey) || ! $disk->exists($mp3Key)) {
                    throw new \RuntimeException('One or more test objects missing after upload.');
                }

                return true;
            });

            if ($this->option('keep')) {
                $this->warn('Skipped cleanup (--keep). Objects left at:');
                $this->line("  {$jsonKey}");
                $this->line("  {$mp3Key}");
            } else {
                $this->components->task('Delete test objects', function () use ($disk, $jsonKey, $mp3Key) {
                    $disk->delete([$jsonKey, $mp3Key]);

                    return true;
                });
            }

            $this->newLine();
            $this->info('Voice training storage is configured correctly.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('Storage test failed: ' . $e->getMessage());

            if ($driver === 's3') {
                $this->line('');
                $this->line('Common causes:');
                $this->line('  • Access key belongs to a different AWS account than the bucket');
                $this->line('  • IAM policy not attached to the user that owns this access key');
                $this->line('  • Wrong bucket name, region, or secret key');
                $this->line('  • Run: composer install --no-dev (needs league/flysystem-aws-s3-v3)');
            }

            return self::FAILURE;
        }
    }

    private function describeUnreadableBody(string $raw, string $driver, string $jsonError): string
    {
        $length = strlen($raw);
        $preview = Str::limit(trim($raw), 240);

        if ($length === 0) {
            return 'Read returned empty body (check s3:GetObject on the IAM policy).';
        }

        if (str_starts_with(ltrim($raw), '<?xml') || str_contains($raw, '<Error>')) {
            $code = preg_match('/<Code>([^<]+)<\/Code>/', $raw, $matches) ? $matches[1] : 'Unknown';
            $message = preg_match('/<Message>([^<]+)<\/Message>/', $raw, $matches) ? $matches[1] : 'Unknown S3 error';

            return "S3 returned XML instead of JSON ({$code}: {$message}). Add s3:GetObject for arn:aws:s3:::your-bucket/* to the IAM policy.";
        }

        return "JSON parse error ({$jsonError}). Read {$length} bytes starting with: {$preview}";
    }
}
