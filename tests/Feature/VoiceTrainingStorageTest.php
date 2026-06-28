<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VoiceTrainingStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_voice_training_storage_command_passes_on_local_disk(): void
    {
        Storage::fake('voice_training');

        $exitCode = Artisan::call('voice-training:test-storage');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString(
            'Voice training storage is configured correctly.',
            Artisan::output()
        );
    }
}
