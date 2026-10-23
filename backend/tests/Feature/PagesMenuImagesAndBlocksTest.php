<?php

namespace Tests\Feature;

use App\Livewire\Admin\Resources\Edit;
use App\Models\HomeItem;
use App\Models\HomeSection;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Setting;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Models\User;
use App\Support\HomeBlocks;
use App\Support\IiprofPages;
use App\Support\Import\MarkdownTidy;
use App\Support\Import\PageImporter;
use App\Support\MenuImages;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class PagesMenuImagesAndBlocksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function content(string $path): string
    {
        return (string) $this->getJson('/api/v1/page?path='.$path)->assertOk()->json('data.content');
    }

    // ------------------------------------------------------------------ iiprof.com page texts

    public function test_pages_get_the_iiprof_com_texts(): void
    {
        $this->assertTrue(IiprofPages::applied());
        $this->assertGreaterThanOrEqual(70, count(IiprofPages::data()['pages']));

        $this->assertStringContainsString('Nonbank financial companies', $this->content('/industry/banks-nbfc'));
        $this->assertStringContainsString('[Register now](#register)', $this->content('/industry/banks-nbfc'));
        $this->assertStringContainsString('## Know IIP', $this->content('/about-us'));
        $this->assertStringContainsString('Staffing and consulting services', $this->content('/industry/staffing-solution'));
        $this->assertStringContainsString('Terms of Use', $this->content('/terms-conditions'));
        $this->assertStringContainsString('#### Perspective', $this->content('/industry/alumni-community'));

        // Pages where this site's version is more complete keep it.
        $this->assertSame(15, substr_count($this->content('/credibility'), '[Download]('));
        $this->assertStringContainsString('## Coming soon', $this->content('/bookings'));

        // Biographies and quotes.
        $this->assertStringContainsString('Project Management Consultant (PMC)', (string) TeamMember::query()->where('name', 'Mr. Chandan Roy')->value('bio'));
        $member = collect($this->getJson('/api/v1/team?group=director-council')->json('data.members'))->firstWhere('name', 'Mr. Chandan Roy');
        $this->assertStringContainsString('<li>Financial Engineer</li>', $member['bio_html']);
        $this->assertStringContainsString('very useful in my work context', (string) Testimonial::query()->where('name', 'Ayushi More')->value('quote'));
    }

    public function test_pages_edited_in_the_admin_are_kept(): void
    {
        Setting::query()->where('key', IiprofPages::MARKER)->delete();
        Page::query()->where('slug', 'industry-fdi')->update(['content' => 'Our own FDI text']);
        Page::query()->where('slug', 'industry-ipo')->update(['content' => '']);

        $report = IiprofPages::applyOnce();

        $this->assertStringStartsWith('kept', $report['/industry/fdi']);
        $this->assertSame('Our own FDI text', Page::query()->where('slug', 'industry-fdi')->value('content'));
        $this->assertStringStartsWith('copied', $report['/industry/ipo']);
        $this->assertSame([], IiprofPages::applyOnce(), 'runs once');
    }

    public function test_a_shipped_text_whose_images_were_copied_still_counts_as_not_edited(): void
    {
        $page = Page::query()->where('slug', 'about-us')->firstOrFail();
        $original = (string) IiprofPages::content('/about-us');
        DB::table('imported_files')->insert(['path' => 'imports/imp-3-abcdef.jpg', 'source_hash' => md5('x'), 'source_url' => 'https://iiprof.com/public/img/imp-3.jpg', 'mime' => 'image/jpeg', 'bytes' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $page->forceFill(['content' => str_replace('https://iiprof.com/public/img/imp-3.jpg', '/storage/imports/imp-3-abcdef.jpg', $original)])->saveQuietly();

        $this->assertTrue((new PageImporter)->untouched($page->fresh()));

        $page->forceFill(['content' => $page->content."\n\nOne more line."])->saveQuietly();
        $this->assertFalse((new PageImporter)->untouched($page->fresh()));
    }

    public function test_markdown_tidy(): void
    {
        $md = "## **MISSION**\n\n##\n\n- **To serve the community.**\n\n**Perspective**\n\nA line broken  \nin the middle.\n\n[JOIN US](https://iiprof.com/member/registration)\n\nWant to be an IPO member? Register Now";

        $this->assertSame(
            "## Mission\n\n- To serve the community.\n\n#### Perspective\n\nA line broken in the middle.\n\n[JOIN US](#register)\n\nWant to be an IPO member? [Register now](#register)",
            MarkdownTidy::clean($md),
        );
        $this->assertSame("IIP's competency", MarkdownTidy::headingCase("IIP'S COMPETENCY"));
        $this->assertSame('How It works', MarkdownTidy::headingCase('How It works'));
    }

    // ------------------------------------------------------------------ menu images

    public function test_every_menu_section_has_a_featured_image(): void
    {
        $this->assertTrue(MenuImages::applied());
        $menu = collect($this->getJson('/api/v1/site')->assertOk()->json('data.menu'))->keyBy('label');

        foreach (['Home', 'About Us', 'Hunar', 'Jeevika', 'Khazana', 'Health', 'Philanthropy', 'More'] as $label) {
            $this->assertStringEndsWith('.webp', (string) $menu[$label]['image_url'], $label);
        }
        $lender = collect($menu['Khazana']['children'])->firstWhere('label', 'Lender');
        $this->assertStringEndsWith('/media/menu/khazana-lender.webp', $lender['image_url']);
        $this->assertFileExists(public_path('media/menu/khazana-lender.webp'));

        // Pages without a banner of their own use the image of their menu item, or of the nearest one above.
        $this->getJson('/api/v1/page?path=/industry/banks-nbfc')->assertOk()
            ->assertJsonPath('data.featured_image_url', null)
            ->assertJsonPath('data.banner.image_url', rtrim((string) config('app.url'), '/').'/media/menu/khazana-lender.webp');
        $this->assertStringEndsWith('/media/menu/about-us-management.webp', $this->getJson('/api/v1/page?path=/management/director-council')->json('data.banner.image_url'));

        // A page's own banner wins.
        Page::query()->where('slug', 'industry-banks-nbfc')->update(['featured_image' => 'pages/own.jpg']);
        $this->assertStringEndsWith('/storage/pages/own.jpg', $this->getJson('/api/v1/page?path=/industry/banks-nbfc')->json('data.banner.image_url'));
    }

    public function test_menu_images_are_edited_in_the_admin_and_removing_one_is_permanent(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $khazana = MenuItem::query()->where('label', 'Khazana')->whereNull('parent_id')->firstOrFail();

        Livewire::test(Edit::class, ['resource' => 'menu', 'id' => (string) $khazana->id])
            ->assertSee('Featured image')
            ->set('removeImage.image', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($khazana->fresh()->image);
        $this->assertFileExists(public_path('media/menu/khazana.webp'), 'bundled files are never deleted');
        $this->assertSame(0, MenuImages::applyOnce());
        $this->assertNull($khazana->fresh()->image);
    }

    // ------------------------------------------------------------------ homepage blocks

    public function test_new_homepage_blocks_with_their_items(): void
    {
        $sections = collect($this->getJson('/api/v1/home')->assertOk()->json('data.sections'))->keyBy('key');

        $this->assertSame(array_values(array_diff(\App\Support\MembershipTraining::DEFAULT_ORDER, ['faculty'])), $sections->keys()->all());
        $this->assertCount(6, $sections['pillars']['items']);
        $this->assertSame('Hunar', $sections['pillars']['items'][0]['title']);
        $this->assertStringEndsWith('/media/menu/hunar.webp', $sections['pillars']['items'][0]['image_url']);
        $this->assertSame(['2012', '15', '70+', '6'], array_column($sections['impact']['items'], 'value'));
        $this->assertCount(9, $sections['benefits']['items']);
        $this->assertSame('FMIIP', collect($sections['membership']['items'])->firstWhere('title', 'Fellow Member')['value']);
        $this->assertCount(6, $sections['programmes']['items']);
        $this->assertSame('Ayushi More', $sections['testimonials']['items'][0]['name']);
        $this->assertSame('/credibility', $sections['partners']['items'][0]['button_url']);
        $this->assertStringContainsString('<a href="/membership">Membership</a>', collect($sections['faq']['items'])->firstWhere('title', 'Who can become a member?')['description_html']);
        $this->assertSame('#register', $sections['join']['button_url']);
        $this->assertSame([], $sections['join']['items']);

        // A block without items is left out; switched-off items too.
        HomeItem::query()->in('faq')->update(['is_active' => false]);
        $this->assertNotContains('faq', collect($this->getJson('/api/v1/home')->json('data.sections'))->pluck('key'));
    }

    public function test_an_order_changed_in_the_admin_is_kept_when_the_blocks_are_added(): void
    {
        // State of the previous version: seven blocks, contact moved to the top.
        HomeItem::query()->delete();
        HomeSection::query()->whereNotIn('key', ['hero', 'stats', 'about', 'highlights', 'events', 'gallery', 'contact'])->delete();
        Setting::query()->where('key', HomeBlocks::MARKER)->delete();
        HomeSection::query()->where('key', 'contact')->update(['sort_order' => 0]);

        HomeBlocks::seedOnce();

        $keys = HomeSection::query()->ordered()->pluck('key')->all();
        $this->assertSame(['contact', 'hero', 'stats', 'about', 'highlights', 'events', 'gallery'], array_slice($keys, 0, 7));
        $this->assertCount(count(HomeSection::SECTIONS), $keys);
        $this->assertSame(6, HomeItem::query()->in('pillars')->count());

        // Deleted items stay deleted.
        HomeItem::query()->in('pillars')->delete();
        $this->assertSame([], HomeBlocks::seedOnce());
        $this->assertSame(0, HomeItem::query()->in('pillars')->count());
    }

    public function test_block_items_are_managed_per_block(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();
        $tile = HomeItem::query()->in('pillars')->firstOrFail();

        $this->actingAs($admin)->get('/admin/manage/feature-tiles')->assertOk()->assertSee('Khazana')->assertDontSee('Who can become a member?');
        $this->actingAs($admin)->get('/admin/manage/faqs')->assertOk()->assertSee('Who can become a member?');
        $this->actingAs($admin)->get("/admin/manage/faqs/{$tile->id}/edit")->assertNotFound();
        $this->actingAs($editor)->get('/admin/manage/membership-categories')->assertOk()->assertSee('FMIIP');
        $this->actingAs($admin)->get('/admin/manage/home-sections')->assertOk()->assertSee('Feature tiles')->assertSee('6 tiles');

        $this->actingAs($admin);
        Livewire::test(Edit::class, ['resource' => 'faqs'])
            ->set('data.title', 'Is there a fee?')
            ->set('data.description', 'See **Membership**.')
            ->call('save')
            ->assertHasNoErrors();

        $faq = HomeItem::query()->where('title', 'Is there a fee?')->firstOrFail();
        $this->assertSame('faq', $faq->section);
        $this->assertSame(8, $faq->sort_order);
    }
}
