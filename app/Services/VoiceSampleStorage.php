<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\VoiceSample;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

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
        string $recordingSource = 'manual_record',
    ): VoiceSample {
        $uuid = (string) Str::uuid();
        $now = now();
        $prefix = sprintf(
            'voice-training/%s/%s/%s',
            $organization->slug,
            $now->format('Y'),
            $now->format('m')
        );

        $audio = $this->prepareAudioForUpload($file);
        $audioKey = "{$prefix}/{$uuid}.{$audio['extension']}";

        Storage::disk($this->disk())->put(
            $audioKey,
            file_get_contents($audio['path']),
            ['ContentType' => $audio['content_type']]
        );
        @unlink($audio['path']);

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
            'recording_source' => $recordingSource,
        ];

        $metadataKey = "{$prefix}/{$uuid}.json";
        Storage::disk($this->disk())->put(
            $metadataKey,
            json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            ['ContentType' => 'application/json']
        );

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
            's3_key' => $audioKey,
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

    /**
     * @return array{path: string, extension: string, content_type: string}
     */
    private function prepareAudioForUpload(UploadedFile $file): array
    {
        if ($this->shouldTranscodeToMp3($file)) {
            try {
                $mp3Path = $this->transcodeToMp3($file);
                if (is_file($mp3Path) && filesize($mp3Path) > 0) {
                    return [
                        'path' => $mp3Path,
                        'extension' => 'mp3',
                        'content_type' => 'audio/mpeg',
                    ];
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $extension = $this->extensionForMime($mime, $file);
        $tempPath = sys_get_temp_dir().'/'.Str::uuid().'.'.$extension;
        copy($file->getRealPath(), $tempPath);

        return [
            'path' => $tempPath,
            'extension' => $extension,
            'content_type' => $mime,
        ];
    }

    private function shouldTranscodeToMp3(UploadedFile $file): bool
    {
        if (app()->runningUnitTests()) {
            return false;
        }

        $mime = strtolower($file->getMimeType() ?: '');
        if (in_array($mime, ['audio/mpeg', 'audio/mp3'], true)) {
            return false;
        }

        return $this->ffmpegAvailable();
    }

    private function extensionForMime(string $mime, UploadedFile $file): string
    {
        $mime = strtolower($mime);

        if (str_contains($mime, 'webm')) {
            return 'webm';
        }
        if (str_contains($mime, 'mpeg') || str_contains($mime, 'mp3')) {
            return 'mp3';
        }
        if (str_contains($mime, 'wav')) {
            return 'wav';
        }
        if (str_contains($mime, 'ogg')) {
            return 'ogg';
        }
        if (str_contains($mime, 'm4a')) {
            return 'm4a';
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: '');

        return $extension !== '' ? $extension : 'webm';
    }

    private function transcodeToMp3(UploadedFile $file): string
    {
        $outputPath = sys_get_temp_dir().'/'.Str::uuid().'.mp3';

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
            throw new \RuntimeException(trim($process->getErrorOutput()) ?: 'ffmpeg transcoding failed.');
        }

        return $outputPath;
    }

    private function ffmpegAvailable(): bool
    {
        if (! $this->canRunProcesses()) {
            return false;
        }

        try {
            $process = new Process(['ffmpeg', '-version']);
            $process->run();

            return $process->isSuccessful();
        } catch (Throwable $e) {
            return false;
        }
    }

    private function canRunProcesses(): bool
    {
        if (! function_exists('proc_open')) {
            return false;
        }

        $disabled = array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));

        return ! in_array('proc_open', $disabled, true);
    }
}
