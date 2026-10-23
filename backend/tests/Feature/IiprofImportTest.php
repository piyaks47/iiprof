<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Event;
use App\Models\FooterColumn;
use App\Models\GalleryItem;
use App\Models\HeroSlide;
use App\Models\HomeSection;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Setting;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Support\IiprofContent;
use App\Support\SiteSettings;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IiprofImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_fresh_install_has_the_iiprof_com_structure(): void
    {
        $top = MenuItem::query()->whereNull('parent_id')->ordered()->pluck('label')->all();
        $this->assertSame(['Home', 'About Us', 'Community', 'Hunar', 'Jeevika', 'Khazana', 'DuniyaBazar', 'Bookings',
            'Consultancy', 'Health', 'Talent-Register', 'Philanthropy', 'Edu10', 'More'], $top);
        $this->assertGreaterThan(100, MenuItem::query()->count());
        $this->assertSame('/management/director-council', MenuItem::query()->where('label', 'Director Council')->firstOrFail()->link());
        $this->assertSame('/jeevika/freelencer', MenuItem::query()->where('label', 'Freelance')->firstOrFail()->link()); // old URL kept
        $this->assertTrue(MenuItem::query()->where('label', 'DuniyaBazar')->firstOrFail()->new_tab);

        $this->assertSame(5, TeamMember::query()->where('group', 'director-council')->count());
        $this->assertSame(3, Testimonial::query()->count());
        $this->assertSame(4, GalleryItem::query()->count());
        $this->assertStringContainsString('event_1684929230.jpg', Event::query()->where('slug', 'scholarship-test')->value('image'));
        foreach (['about-us', 'membership', 'credibility', 'how-it-works', 'hunar-book-my-seat', 'contact-us', 'management-director-council'] as $slug) {
            $this->assertTrue(Page::query()->where('slug', $slug)->published()->exists(), $slug);
        }
        $this->assertFalse(Page::query()->where('slug', 'about')->exists());
        $membership = Page::query()->where('slug', 'membership')->value('content');
        $this->assertStringContainsString('#### Fellow Member (FMIIP)', $membership); // iiprof.com's own wording
        $this->assertStringNotContainsString('iiprof.com/member', $membership); // registration links point at this site

        $s = app(SiteSettings::class);
        $this->assertSame('info@iiprof.com', $s->get('contact_email'));
        $this->assertSame('https://twitter.com/IIPOrganization', $s->get('x_url'));
        $this->assertTrue(IiprofContent::imported());

        // Pages answer at the old iiprof.com addresses through the API.
        $this->getJson('/api/v1/pages/management-director-council')->assertOk()->assertJsonPath('data.title', 'Director Council');
        $this->getJson('/api/v1/team?group=director-council')->assertOk()
            ->assertJsonCount(5, 'data.members')->assertJsonPath('data.members.1.name', 'Dr. Neelam Saxena');
        $this->getJson('/api/v1/testimonials')->assertOk()
            ->assertJsonPath('data.1.video_embed_url', 'https://www.youtube-nocookie.com/embed/G04IWiW4JNg');
        $this->getJson('/api/v1/gallery')->assertOk()->assertJsonCount(4, 'data');
    }

    public function test_later_deploys_never_overwrite_admin_edits(): void
    {
        MenuItem::query()->where('label', 'Edu10')->update(['label' => 'Edu 10+']);
        Setting::query()->where('key', 'facebook_url')->update(['value' => null]);

        $this->seed(DatabaseSeeder::class); // every deploy runs the seeders

        $this->assertTrue(MenuItem::query()->where('label', 'Edu 10+')->exists());
        $this->assertNull(Setting::query()->where('key', 'facebook_url')->value('value'));
    }

    public function test_upgrade_replaces_untouched_starter_content_and_keeps_edits(): void
    {
        // Simulate an installation of the previous version: starter content, no import marker.
        Setting::query()->where('key', IiprofContent::MARKER)->delete();
        HeroSlide::query()->get()->each->delete();
        foreach (['Accelerate Your Career Growth', 'Expand Community Outreach', 'Grow Your Network'] as $title) {
            HeroSlide::query()->create(['title' => $title]);
        }
        Announcement::query()->delete();
        Announcement::query()->create(['text' => 'Our own announcement']); // edited → must stay
        GalleryItem::query()->get()->each->delete();
        Page::query()->where('slug', 'about-us')->update(['slug' => 'about', 'content' => "## Our story\n\nTell visitors who you are. Edit this page."]);

        $report = IiprofContent::importOnce();

        $this->assertSame('imported', $report['hero slides']);
        $this->assertSame('Expand Community Outreach', HeroSlide::query()->ordered()->value('title'));
        $this->assertStringContainsString('kept', $report['announcements']);
        $this->assertSame(['Our own announcement'], Announcement::query()->pluck('text')->all());
        $this->assertSame(4, GalleryItem::query()->count());
        $this->assertTrue(Page::query()->where('slug', 'about-us')->exists());
        $this->assertFalse(Page::query()->where('slug', 'about')->exists());
        $this->assertSame(['content' => 'already imported'], IiprofContent::importOnce());

        // --force (php deploy/deploy.php import-content) replaces edited content too.
        $this->artisan('iiprof:import-content', ['--force' => true])->assertSuccessful();
        $this->assertCount(3, Announcement::query()->get());
    }

    public function test_images_are_copied_from_iiprof_com_to_this_server(): void
    {
        Storage::fake('public');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
        Http::fake([
            '*gallery_1677072224*' => Http::response('not found', 404),
            'iiprof.com/*' => Http::response($png, 200, ['Content-Type' => 'image/png']),
            '*' => Http::response('nope', 500),
        ]);
        HeroSlide::query()->first()->update(['image' => 'https://example.org/other-site.jpg']); // other hosts are left alone

        $this->artisan('iiprof:localize-images')->assertSuccessful();

        $logo = app(SiteSettings::class)->get('logo');
        $this->assertStringStartsWith('imports/logo-black-', $logo);
        Storage::disk('public')->assertExists($logo);
        $this->assertStringStartsWith('imports/', TeamMember::query()->where('name', 'Dr. Neelam Saxena')->value('photo'));
        $this->assertSame('https://example.org/other-site.jpg', HeroSlide::query()->ordered()->value('image'));
        $this->assertStringContainsString('gallery_1677072224.jpg', GalleryItem::query()->where('image', 'like', 'http%')->value('image')); // failed one keeps its URL
        $howItWorks = Page::query()->where('slug', 'how-it-works')->value('content');
        $this->assertStringNotContainsString('iiprof.com/public/img/howpic1.jpg', $howItWorks);
        $this->assertStringContainsString('/storage/imports/howpic1-', $howItWorks);
    }
}
