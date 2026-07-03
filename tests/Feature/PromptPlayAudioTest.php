<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Option;
use App\Models\Organization;
use App\Models\Prompt;
use App\Models\PromptOptionAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PromptPlayAudioTest extends TestCase
{
    use RefreshDatabase;

    protected Lesson $lesson;

    protected Prompt $prompt;

    protected Option $option;

    protected string $audioRelativePath;

    protected function setUp(): void
    {
        parent::setUp();

        $organization = Organization::create([
            'name' => 'Audio Org',
            'slug' => 'audio-org',
            'access_mode' => 'open',
            'is_active' => true,
        ]);

        $course = Course::create([
            'title' => 'Audio Course',
            'slug' => 'audio-course',
            'is_active' => true,
        ]);

        $organization->courses()->attach($course->id, ['is_org_wide' => true]);

        Organization::create([
            'name' => 'Default',
            'slug' => 'default',
            'access_mode' => 'open',
            'is_active' => true,
        ])->courses()->attach($course->id, ['is_org_wide' => true]);

        $this->lesson = Lesson::create([
            'title' => 'Audio Lesson',
            'slug' => 'audio-lesson',
            'course_id' => $course->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->prompt = Prompt::create([
            'lesson_id' => $this->lesson->id,
            'prompt_text' => 'Pick a word',
            'template' => 'I like {}.',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->option = Option::create([
            'prompt_id' => $this->prompt->id,
            'label' => 'apples',
            'image_path' => 'images/food/apples.png',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->audioRelativePath = "tts/lesson{$this->lesson->id}/p{$this->prompt->id}_o{$this->option->id}.mp3";
    }

    private function seedLegacySentenceAudio(): void
    {
        $storagePath = storage_path("app/public/{$this->audioRelativePath}");
        File::ensureDirectoryExists(dirname($storagePath));
        file_put_contents($storagePath, 'fake-audio');

        $publicPath = public_path("storage/{$this->audioRelativePath}");
        File::ensureDirectoryExists(dirname($publicPath));
        copy($storagePath, $publicPath);

        PromptOptionAsset::create([
            'prompt_id' => $this->prompt->id,
            'option_id' => $this->option->id,
            'generated_sentence' => 'I like apples.',
            'audio_path' => "/storage/{$this->audioRelativePath}",
        ]);
    }

    protected function tearDown(): void
    {
        @unlink(public_path("storage/{$this->audioRelativePath}"));
        @unlink(storage_path("app/public/{$this->audioRelativePath}"));

        parent::tearDown();
    }

    public function test_prompts_play_embeds_legacy_sentence_audio_when_option_path_missing(): void
    {
        $this->seedLegacySentenceAudio();

        $response = $this->get(route('prompts.play', $this->lesson));

        $response->assertOk();

        /** @var \App\Models\Lesson $lesson */
        $lesson = $response->viewData('lesson');
        $option = $lesson->prompts->first()->options->first();

        $this->assertSame(
            asset("/storage/{$this->audioRelativePath}"),
            $option->sentence_audio_path
        );
    }

    public function test_prompt_model_endpoint_returns_legacy_sentence_audio(): void
    {
        $this->seedLegacySentenceAudio();

        $response = $this->get(route('prompts.model', [
            'promptId' => $this->prompt->id,
            'optionId' => $this->option->id,
        ]));

        $response->assertOk();
        $response->assertJson([
            'generated_sentence' => 'I like apples.',
            'audio_url' => asset("/storage/{$this->audioRelativePath}"),
        ]);
    }
}
