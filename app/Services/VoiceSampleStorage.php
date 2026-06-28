<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\VoiceSample;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class VoiceSampleStorage
{
    public function store(
        UploadedFile $file,
        Organization $organization,
        int $lessonId,
        string $targetText,
        int $age,
        string $gender,
        string $nativeLanguage,
        ?int $promptId = null,
        ?int $optionId = null,
        ?int $vocabularyId = null,
        ?int $durationMs = null,
    ): VoiceSample {
        $uuid = (string) Str::uuid();
        $now = now();
        $prefix = sprintf(
            'voice-training/%s/%s/%s',
            $organization->slug,
            $now->format('Y'),
            $now->format('m')
        );

        $mp3Path = $this->transcodeToMp3($file);
        $mp3Key = "{$prefix}/{$uuid}.mp3";

        Storage::disk($this->disk())->put($mp3Key, file_get_contents($mp3Path));
        @unlink($mp3Path);

        $metadata = [
            'target_text' => $targetText,
            'age' => $age,
            'gender' => $gender,
            'native_language' => $nativeLanguage,
            'organization_slug' => $organization->slug,
            'lesson_id' => $lessonId,
            'prompt_id' => $promptId,
            'option_id' => $optionId,
            'vocabulary_id' => $vocabularyId,
            'recorded_at' => $now->toIso8601String(),
            'duration_ms' => $durationMs,
        ];

        $metadataKey = "{$prefix}/{$uuid}.json";
        Storage::disk($this->disk())->put($metadataKey, json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

        return VoiceSample::create([
            'organization_id' => $organization->id,
            'lesson_id' => $lessonId,
            'vocabulary_id' => $vocabularyId,
            'prompt_id' => $promptId,
            'option_id' => $optionId,
            'target_text' => $targetText,
            'age' => $age,
            'gender' => $gender,
            'native_language' => $nativeLanguage,
            's3_key' => $mp3Key,
            'metadata_s3_key' => $metadataKey,
            'duration_ms' => $durationMs,
            'mime_original' => $file->getMimeType(),
            'recorded_at' => $now,
        ]);
    }

    public function disk(): string
    {
        return config('filesystems.voice_training_disk', 'voice_training');
    }

    private function transcodeToMp3(UploadedFile $file): string
    {
        $outputPath = sys_get_temp_dir() . '/' . Str::uuid() . '.mp3';

        if (app()->runningUnitTests() || ! $this->ffmpegAvailable()) {
            copy($file->getRealPath(), $outputPath);

            return $outputPath;
        }

        $process = new Process([
            'ffmpeg',
            '-y',
            '-i', $file->getRealPath(),
            '-vn',
            '-acodec', 'libmp3lame',
            '-q:a', '4',
            $outputPath,
        ]);
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $outputPath;
    }

    private function ffmpegAvailable(): bool
    {
        $process = new Process(['ffmpeg', '-version']);
        $process->run();

        return $process->isSuccessful();
    }
}
