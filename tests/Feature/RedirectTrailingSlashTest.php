<?php

namespace Tests\Feature;

use App\Models\Organization;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectTrailingSlashTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_o_org_trailing_slash_does_not_404(): void
    {
        $org = Organization::where('slug', 'default')->firstOrFail();
        $response = $this->get('/o/' . $org->slug . '/');
        $this->assertFalse($response->isNotFound(), 'Trailing slash should redirect or load, not 404');
    }

    public function test_o_org_without_trailing_loads(): void
    {
        $org = Organization::where('slug', 'default')->firstOrFail();
        $response = $this->get('/o/' . $org->slug);
        $response->assertOk();
    }
}
