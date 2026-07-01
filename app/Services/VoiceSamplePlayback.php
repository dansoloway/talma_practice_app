<?php

namespace App\Services;

use App\Models\VoiceSample;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VoiceSamplePlayback
{
    public function disk(): string
    {
        return config('filesystems.voice_training_disk', 'voice_training');
    }

    public function isS3(): bool
    {
        return config('filesystems.disks.'.$this->disk().'.driver') === 's3';
    }

    public function respond(VoiceSample $sample): RedirectResponse|StreamedResponse
    {
        $disk = Storage::disk($this->disk());

        if (! $disk->exists($sample->s3_key)) {
            abort(404, 'Audio file not found.');
        }

        if ($this->isS3()) {
            $url = $disk->temporaryUrl($sample->s3_key, now()->addMinutes(15));

            return redirect()->away($url);
        }

        return $disk->response($sample->s3_key, headers: [
            'Content-Type' => $this->contentTypeForKey($sample->s3_key, $sample->mime_original),
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    private function contentTypeForKey(string $key, ?string $mimeOriginal): string
    {
        if ($mimeOriginal) {
            return $mimeOriginal;
        }

        $extension = strtolower(pathinfo($key, PATHINFO_EXTENSION));

        return match ($extension) {
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'webm' => 'audio/webm',
            'm4a' => 'audio/mp4',
            default => 'application/octet-stream',
        };
    }
}
