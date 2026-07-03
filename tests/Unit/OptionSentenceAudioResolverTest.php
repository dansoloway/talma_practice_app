<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Option;
use App\Models\Organization;
use App\Models\Prompt;
use App\Models\PromptOptionAsset;
use App\Services\Tts\OptionSentenceAudioResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class OptionSentenceAudioResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolver_uses_legacy_prompt_option_asset_when_option_path_missing(): void
    {
        $organization = Organization::create([
            'name' => 'Audio Org',
            'slug' => 'audio-org-resolver',
            'access_mode' => 'open',
            'is_active' => true,
        ]);

        $course = Course::create([
            'title' => 'Audio Course',
            'slug' => 'audio-course-resolver',
            'is_active' => true,
        ]);

        $organization->courses()->attach($course->id, ['is_org_wide' => true]);

        $lesson = Lesson::create([
            'title' => 'Audio Lesson',
            'slug' => 'audio-lesson-resolver',
            'course_id' => $course->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $prompt = Prompt::create([
            'lesson_id' => $lesson->id,
            'prompt_text' => 'Pick a word',
            'template' => 'I like {}.',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $option = Option::create([
            'prompt_id' => $prompt->id,
            'label' => 'apples',
            'image_path' => 'images/food/apples.png',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $relativePath = "tts/lesson{$lesson->id}/p{$prompt->id}_o{$option->id}.mp3";
        $storagePath = storage_path("app/public/{$relativePath}");
        File::ensureDirectoryExists(dirname($storagePath));
        file_put_contents($storagePath, 'fake-audio');

        $publicPath = public_path("storage/{$relativePath}");
        File::ensureDirectoryExists(dirname($publicPath));
        copy($storagePath, $publicPath);

        PromptOptionAsset::create([
            'prompt_id' => $prompt->id,
            'option_id' => $option->id,
            'generated_sentence' => 'I like apples.',
            'audio_path' => "/storage/{$relativePath}",
        ]);

        $resolver = app(OptionSentenceAudioResolver::class);
        $url = $resolver->resolveUrl($option->fresh());

        $this->assertSame(asset("/storage/{$relativePath}"), $url);

        @unlink($publicPath);
        @unlink($storagePath);
    }
}
