<?php

namespace App\Services;

use App\Models\VoiceSample;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class VoiceSamplePlayback
{
    public function disk(): string
    {
        return config('filesystems.voice_training_disk', 'voice_training');
    }

    public function contentType(VoiceSample $sample): string
    {
        return $this->contentTypeForKey($sample->s3_key, $sample->mime_original);
    }

    public function respond(VoiceSample $sample): Response
    {
        $disk = Storage::disk($this->disk());
        $key = $sample->s3_key;

        try {
            if (! $disk->exists($key)) {
                abort(404, 'Audio file not found.');
            }

            $content = $disk->get($key);
        } catch (Throwable $e) {
            report($e);
            abort(502, 'Unable to load audio from storage.');
        }

        if ($content === null || $content === '') {
            abort(404, 'Audio file is empty.');
        }

        return response($content, 200, [
            'Content-Type' => $this->contentType($sample),
            'Content-Length' => (string) strlen($content),
            'Content-Disposition' => 'inline; filename="'.basename($key).'"',
            'Cache-Control' => 'private, max-age=3600',
            'Accept-Ranges' => 'bytes',
        ]);
    }

    private function contentTypeForKey(string $key, ?string $mimeOriginal): string
    {
        $extension = strtolower(pathinfo($key, PATHINFO_EXTENSION));

        if ($extension === 'webm') {
            return 'audio/webm';
        }

        if ($mimeOriginal && $mimeOriginal !== 'video/webm') {
            return $mimeOriginal;
        }

        return match ($extension) {
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'm4a' => 'audio/mp4',
            default => 'application/octet-stream',
        };
    }
}
