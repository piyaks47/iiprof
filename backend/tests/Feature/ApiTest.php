<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Models\Page;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_site_endpoint_returns_settings_and_menu(): void
    {
        $this->getJson('/api/v1/site')
            ->assertOk()
            ->assertJsonPath('data.settings.site_name', 'International Institute of Professionals')
            ->assertJsonPath('data.settings.tagline', 'Brain Bank of Global Talents')
            ->assertJsonPath('data.menu.0.label', 'Home')
            ->assertJsonPath('data.menu.0.url', '/')
            ->assertJsonMissingPath('data.settings.logo');
    }

    public function test_published_page_is_returned_with_sanitised_html(): void
    {
        Page::query()->create([
            'title' => 'Safe page',
            'content' => "## Hi\n\n<script>alert(1)</script>",
            'status' => PageStatus::Published,
        ]);

        $html = $this->getJson('/api/v1/pages/safe-page')
            ->assertOk()
            ->assertJsonPath('data.title', 'Safe page')
            ->json('data.content_html');

        $this->assertStringContainsString('<h2>Hi</h2>', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_draft_and_missing_pages_return_404(): void
    {
        Page::query()->create(['title' => 'Secret draft', 'status' => PageStatus::Draft]);

        $this->getJson('/api/v1/pages/secret-draft')->assertNotFound();
        $this->getJson('/api/v1/pages/nope')->assertNotFound();
        $this->getJson('/api/v1/pages')->assertOk()->assertJsonMissing(['slug' => 'secret-draft']);
    }

    public function test_reserved_slugs_are_never_generated(): void
    {
        $page = Page::query()->create(['title' => 'Admin', 'status' => PageStatus::Draft]);

        $this->assertSame('admin-page', $page->slug);
    }
}
