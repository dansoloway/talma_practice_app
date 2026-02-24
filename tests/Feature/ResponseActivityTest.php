<?php

namespace Tests\Feature;

use App\Models\Lesson;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponseActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_responses_store_requires_valid_payload(): void
    {
        $response = $this->postJson(route('responses.store'), []);
        $response->assertStatus(422);
    }

    public function test_activity_events_store_accepts_valid_payload(): void
    {
        $lesson = Lesson::first();
        $response = $this->postJson(route('activity-events.store'), [
            'activity_type' => 'lesson_view',
            'lesson_id' => $lesson->id,
            'status' => 'completed',
        ]);
        $response->assertSuccessful();
        $response->assertJson(['success' => true]);
    }
}
