<?php

namespace Tests\Feature;

use App\Admin\Registry;
use App\Livewire\Admin\Permissions;
use App\Livewire\Admin\Resources\Edit;
use App\Livewire\Admin\Resources\Index;
use App\Models\Enquiry;
use App\Models\Event;
use App\Models\FooterColumn;
use App\Models\GalleryItem;
use App\Models\HeroSlide;
use App\Models\HomeSection;
use App\Models\MenuItem;
use App\Models\User;
use App\Support\Access;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class HomepageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_seeder_creates_iiprof_content_once(): void
    {
        $this->assertSame(count(HomeSection::SECTIONS), HomeSection::query()->count());
        $this->assertSame(['Expand Community Outreach', 'Accelerate Your Career Growth', 'Grow Your Network'], HeroSlide::query()->ordered()->pluck('title')->all());
        $this->assertSame(['Useful Links', 'Related Links', 'Other Links'], FooterColumn::query()->ordered()->pluck('title')->all());

        $banks = MenuItem::query()->where('label', 'Banks/NBFC')->firstOrFail();
        $this->assertSame(3, $banks->depth());
        $this->assertSame('/industry/banks-nbfc', $banks->link());
        $this->assertSame('/about-us', MenuItem::query()->where('label', 'About IIP')->firstOrFail()->link());
        $this->assertNotNull(MenuItem::query()->where('label', 'About IIP')->value('page_id'));

        HeroSlide::query()->first()->update(['title' => 'Edited']);
        $this->seed(DatabaseSeeder::class);
        $this->assertSame(3, HeroSlide::query()->count());
        $this->assertTrue(HeroSlide::query()->where('title', 'Edited')->exists());
    }

    public function test_site_api_has_menu_tree_announcements_footer_and_socials(): void
    {
        app(\App\Support\SiteSettings::class)->set(['linkedin_url' => 'https://linkedin.com/company/x', 'facebook_url' => null, 'youtube_url' => null, 'x_url' => null]);

        $json = $this->getJson('/api/v1/site')->assertOk()->json('data');

        $khazana = collect($json['menu'])->firstWhere('label', 'Khazana');
        $this->assertSame('Lender', $khazana['children'][0]['label']);
        $this->assertSame('Banks/NBFC', $khazana['children'][0]['children'][0]['label']);
        $this->assertCount(3, $json['announcements']);
        $this->assertSame('Useful Links', $json['footer'][0]['title']);
        $this->assertSame([['key' => 'linkedin', 'label' => 'LinkedIn', 'url' => 'https://linkedin.com/company/x']], $json['socials']);
        $this->assertSame('https://iiprof.com/public/img/logo-black.png', $json['settings']['logo_url']);

        MenuItem::query()->where('label', 'Khazana')->update(['is_active' => false]);
        $this->assertNull(collect($this->getJson('/api/v1/site')->json('data.menu'))->firstWhere('label', 'Khazana'));
    }

    public function test_home_api_follows_section_order_visibility_and_items(): void
    {
        $keys = fn () => collect($this->getJson('/api/v1/home')->assertOk()->json('data.sections'))->pluck('key')->all();

        // Every block in the default order; the faculty block stays hidden until a trainer is added.
        $this->assertSame(array_values(array_diff(\App\Support\MembershipTraining::DEFAULT_ORDER, ['faculty'])), $keys());

        // A gallery without photos is left out.
        GalleryItem::query()->get()->each->delete();
        $this->assertNotContains('gallery', $keys());

        GalleryItem::query()->create(['image' => 'gallery/a.jpg']);
        HomeSection::query()->whereNotIn('key', ['hero', 'stats', 'about', 'highlights', 'events', 'gallery', 'contact'])->update(['is_active' => false]);
        HomeSection::query()->where('key', 'stats')->update(['is_active' => false]);
        HomeSection::query()->where('key', 'contact')->update(['sort_order' => 0]);
        $this->assertSame(['contact', 'hero', 'about', 'highlights', 'events', 'gallery'], $keys());

        $about = collect($this->getJson('/api/v1/home')->json('data.sections'))->firstWhere('key', 'about');
        $this->assertSame('Us', $about['title_accent']);
        $this->assertStringContainsString('<strong>Hunar</strong>', $about['body_html']);
        $this->assertStringStartsWith('https://iiprof.com/', $about['image_url']);
    }

    public function test_event_pages(): void
    {
        $this->getJson('/api/v1/events/scholarship-test')->assertOk()
            ->assertJsonPath('data.title', 'Scholarship Test')
            ->assertJsonPath('data.path', '/events/scholarship-test');

        Event::query()->update(['is_active' => false]);
        $this->getJson('/api/v1/events/scholarship-test')->assertNotFound();
    }

    public function test_enquiry_form(): void
    {
        $this->postJson('/api/v1/enquiries', ['name' => 'A'])->assertUnprocessable()->assertJsonValidationErrors(['email', 'message']);

        $this->postJson('/api/v1/enquiries', ['name' => 'Asha', 'email' => 'asha@example.com', 'phone' => '+91 98300 00000', 'message' => 'Membership please'])
            ->assertCreated();
        $this->assertSame('new', Enquiry::query()->firstOrFail()->status);

        // Honeypot: accepted silently, not stored.
        $this->postJson('/api/v1/enquiries', ['name' => 'Bot', 'email' => 'b@example.com', 'message' => 'spam spam', 'website' => 'x'])->assertCreated();
        $this->assertSame(1, Enquiry::query()->count());

        $this->postJson('/api/v1/enquiries', ['name' => 'C', 'email' => 'c@example.com', 'message' => 'Hello there'])->assertCreated();
        $this->postJson('/api/v1/enquiries', ['name' => 'D', 'email' => 'd@example.com', 'message' => 'Hello there'])->assertCreated();
        $this->postJson('/api/v1/enquiries', ['name' => 'E', 'email' => 'e@example.com', 'message' => 'Hello there'])->assertTooManyRequests();
    }

    public function test_every_resource_screen_renders_for_admin(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (Registry::all() as $key => $resource) {
            $this->actingAs($admin)->get("/admin/manage/{$key}")->assertOk()->assertSee($resource->label());
            if ($resource->canCreate()) {
                $this->actingAs($admin)->get("/admin/manage/{$key}/create")->assertOk();
            }
            if ($record = $resource->query()->first()) {
                $this->actingAs($admin)->get("/admin/manage/{$key}/{$record->getKey()}/edit")->assertOk();
            }
        }

        $this->actingAs($admin)->get('/admin/manage/home-sections/create')->assertNotFound();
        $this->actingAs($admin)->get('/admin/manage/nope')->assertNotFound();
        $this->actingAs($admin)->get('/admin/permissions')->assertOk()->assertSee('Role permissions');
    }

    public function test_slide_crud_with_image_toggle_reorder_and_delete(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(Edit::class, ['resource' => 'hero-slides'])
            ->set('data.title', '')
            ->call('save')
            ->assertHasErrors(['data.title' => 'required'])
            ->set('data.title', 'New horizons')
            ->set('data.button_url', 'javascript:alert(1)')
            ->call('save')
            ->assertHasErrors(['data.button_url'])
            ->set('data.button_url', '/about')
            ->set('uploads.image', UploadedFile::fake()->image('hero.jpg', 1920, 1000))
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $slide = HeroSlide::query()->where('title', 'New horizons')->firstOrFail();
        $this->assertSame(4, $slide->sort_order);
        Storage::disk('public')->assertExists($slide->image);
        $first = $slide->image;

        Livewire::test(Edit::class, ['resource' => 'hero-slides', 'id' => (string) $slide->id])
            ->assertSet('data.title', 'New horizons')
            ->set('uploads.image', UploadedFile::fake()->image('second.png'))
            ->call('save')
            ->assertHasNoErrors();
        Storage::disk('public')->assertMissing($first);

        Livewire::test(Index::class, ['resource' => 'hero-slides'])
            ->call('move', $slide->id, 'up')
            ->call('toggle', $slide->id);
        $this->assertSame(3, $slide->fresh()->sort_order);
        $this->assertFalse($slide->fresh()->is_active);
        $this->assertSame(['Expand Community Outreach', 'Accelerate Your Career Growth', 'New horizons', 'Grow Your Network'], HeroSlide::query()->ordered()->pluck('title')->all());

        $path = $slide->fresh()->image;
        Livewire::test(Index::class, ['resource' => 'hero-slides'])->call('delete', $slide->id);
        $this->assertModelMissing($slide);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_sections_cannot_be_created_or_deleted_but_texts_can_change(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $about = HomeSection::query()->where('key', 'about')->firstOrFail();

        Livewire::test(Edit::class, ['resource' => 'home-sections', 'id' => (string) $about->id])
            ->set('data.title', 'Who')
            ->set('data.title_accent', 'We Are')
            ->set('isActive', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('We Are', $about->fresh()->title_accent);
        $this->assertFalse($about->fresh()->is_active);

        Livewire::test(Index::class, ['resource' => 'home-sections'])->call('delete', $about->id)->assertForbidden();
    }

    public function test_menu_is_limited_to_three_levels_and_cannot_loop(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $banks = MenuItem::query()->where('label', 'Banks/NBFC')->firstOrFail();
        $khazana = MenuItem::query()->where('label', 'Khazana')->firstOrFail();

        Livewire::test(Edit::class, ['resource' => 'menu'])
            ->set('data.label', 'Too deep')
            ->set('data.parent_id', (string) $banks->id)
            ->call('save')
            ->assertHasErrors(['data.parent_id']);

        // Khazana cannot be moved inside its own child.
        $lenders = MenuItem::query()->where('label', 'Lender')->firstOrFail();
        Livewire::test(Edit::class, ['resource' => 'menu', 'id' => (string) $khazana->id])
            ->set('data.parent_id', (string) $lenders->id)
            ->call('save')
            ->assertHasErrors(['data.parent_id']);

        Livewire::test(Edit::class, ['resource' => 'menu'])
            ->set('data.label', 'Gold loans')
            ->set('data.parent_id', (string) $lenders->id)
            ->set('data.url', '/industry/gold-loans')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame(3, MenuItem::query()->where('label', 'Gold loans')->firstOrFail()->depth());
    }

    public function test_enquiries_are_read_only_except_status_and_notes(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $enquiry = Enquiry::query()->create(['name' => 'Asha', 'email' => 'a@example.com', 'message' => 'Hello']);

        Livewire::test(Edit::class, ['resource' => 'enquiries', 'id' => (string) $enquiry->id])
            ->set('data.name', 'Hacked')
            ->set('data.status', 'closed')
            ->set('data.notes', 'Called back')
            ->call('save')
            ->assertHasNoErrors();

        $enquiry->refresh();
        $this->assertSame(['Asha', 'closed', 'Called back'], [$enquiry->name, $enquiry->status, $enquiry->notes]);
    }

    public function test_default_permissions_and_sidebar_per_role(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();
        $member = User::factory()->create();

        $this->actingAs($admin)->get('/admin')->assertSee('Role permissions')->assertSee('Hero slides')->assertSee('Enquiries')
            ->assertDontSee('@js(', false); // Blade directives must not leak into Alpine expressions
        $this->actingAs($editor)->get('/admin')->assertSee('Hero slides')->assertSee('Enquiries')->assertDontSee('Role permissions')->assertDontSee('Site settings');
        $this->actingAs($member)->get('/admin')->assertDontSee('Hero slides')->assertDontSee('Homepage');

        $this->actingAs($editor)->get('/admin/manage/hero-slides')->assertOk();
        $this->actingAs($editor)->get('/admin/permissions')->assertForbidden();
        $this->actingAs($member)->get('/admin/manage/hero-slides')->assertForbidden();
        $this->actingAs($member)->get('/admin/manage/enquiries')->assertForbidden();
    }

    public function test_admin_changes_role_permissions(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(Permissions::class)
            ->assertSet('matrix.editor.hero-slides', '2')
            ->set('matrix.editor.hero-slides', '1')
            ->set('matrix.user.enquiries', '1')
            ->set('matrix.user.settings', '1')
            ->call('save')
            ->assertHasNoErrors();

        $editor = User::factory()->editor()->create();
        $member = User::factory()->create();
        $slide = HeroSlide::query()->firstOrFail();

        $this->assertSame(Access::VIEW, Access::level($editor, 'hero-slides'));

        // View only: list + form open read-only, saving and creating are refused.
        $this->actingAs($editor)->get('/admin/manage/hero-slides')->assertOk()->assertSee('View only')->assertDontSee('New slide');
        $this->actingAs($editor)->get("/admin/manage/hero-slides/{$slide->id}/edit")->assertOk()->assertSee('View only');
        $this->actingAs($editor)->get('/admin/manage/hero-slides/create')->assertForbidden();
        Livewire::actingAs($editor)->test(Edit::class, ['resource' => 'hero-slides', 'id' => (string) $slide->id])->call('save')->assertForbidden();
        Livewire::actingAs($editor)->test(Index::class, ['resource' => 'hero-slides'])->call('delete', $slide->id)->assertForbidden();

        // Members now see Enquiries and Settings (read-only) in their sidebar.
        $this->actingAs($member)->get('/admin')->assertSee('Enquiries')->assertSee('Settings')->assertDontSee('Hero slides');
        $this->actingAs($member)->get('/admin/manage/enquiries')->assertOk();
        $this->actingAs($member)->get('/admin/settings')->assertOk()->assertSee('View only');
        $this->actingAs($member)->get('/admin/users')->assertForbidden();
    }
}
