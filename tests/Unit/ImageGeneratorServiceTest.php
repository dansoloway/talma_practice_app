<?php

namespace Tests\Unit;

use App\Services\ImageGeneration\IconifyImageGenerator;
use App\Services\ImageGeneration\ImageGeneratorService;
use Tests\TestCase;

class ImageGeneratorServiceTest extends TestCase
{
    public function test_iconify_is_enabled_by_default(): void
    {
        config(['services.image.iconify_enabled' => true]);

        $this->assertTrue(app(IconifyImageGenerator::class)->enabled());
    }

    public function test_provider_order_is_configurable(): void
    {
        config(['services.image.providers' => 'openai,iconify']);

        $service = app(ImageGeneratorService::class);
        $this->assertTrue($service->enabled());
    }

    public function test_freepik_excluded_from_default_provider_list(): void
    {
        $providers = config('services.image.providers');

        $this->assertStringNotContainsString('freepik', strtolower((string) $providers));
    }
}
