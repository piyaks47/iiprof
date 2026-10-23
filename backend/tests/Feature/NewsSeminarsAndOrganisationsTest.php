<?php

namespace Tests\Feature;

use App\Livewire\Admin\Permissions;
use App\Livewire\Admin\Resources\Edit;
use App\Models\Enquiry;
use App\Models\Event;
use App\Models\MemberOrganisation;
use App\Models\Post;
use App\Models\Seminar;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\Access;
use App\Support\Dashboard\Metrics;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class NewsSeminarsAndOrganisationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-25 10:00:00');
        $this->seed(DatabaseSeeder::class);
    }

    private function sections(): \Illuminate\Support\Collection
    {
        return collect($this->getJson('/api/v1/home')->assertOk()->json('data.sections'))->keyBy('key');
    }

    // ------------------------------------------------------------------ homepage blocks

    public function test_next_and_past_event_blocks(): void
    {
        $s = $this->sections();

        $next = $s['next_event']['items'];
        $this->assertCount(1, $next);
        $soonest = Event::query()->active()->whereDate('starts_on', '>=', '2026-10-25')->orderBy('starts_on')->firstOrFail();
        $this->assertSame($soonest->title, $next[0]['title']);
        $this->assertArrayHasKey('description_html', $next[0]);

        $past = $s['past_events']['items'];
        $this->assertCount(4, $past);
        $this->assertTrue(collect($past)->every(fn ($e) => $e['is_past'] && $e['starts_on'] < '2026-10-25'));
        $this->assertSame('/view-gallery', $past[0]['media_url']);
        $dates = array_column($past, 'starts_on');
        $this->assertSame($dates, collect($dates)->sortDesc()->values()->all(), 'newest first');

        // What's on lists upcoming events only.
        $this->assertTrue(collect($s['events']['items'])->every(fn ($e) => $e['starts_on'] === null || $e['starts_on'] >= '2026-10-25'));
        $this->assertSame(4, collect($this->getJson('/api/v1/events?when=past')->assertOk()->json('data'))->count());

        // Once the date has passed, the event moves from "next" to "past".
        $this->travelTo($soonest->starts_on->copy()->addDay());
        $s = $this->sections();
        $this->assertNotSame($soonest->title, $s['next_event']['items'][0]['title'] ?? null);
        $this->assertSame($soonest->title, $s['past_events']['items'][0]['title']);
    }

    public function test_news_block_and_pages(): void
    {
        $news = $this->sections()['news'];
        $this->assertCount(4, $news['items']);
        $this->assertTrue($news['items'][0]['is_featured'], 'featured first');
        $this->assertSame('/news/membership-registration-is-open', $news['items'][0]['path']);

        // Future posts and switched-off posts stay hidden until their day.
        Post::query()->create(['type' => 'blog', 'title' => 'Coming next week', 'body' => 'Soon.', 'published_on' => '2026-11-01']);
        Post::query()->where('slug', 'why-up-skilling-matters-in-every-profession')->update(['is_active' => false]);

        $list = $this->getJson('/api/v1/news')->assertOk();
        $this->assertSame(3, $list->json('meta.total'));
        $this->assertSame(['news', 'blog', 'article'], array_column($list->json('types'), 'key'));
        $this->assertSame(['blog'], array_values(array_unique(array_column($this->getJson('/api/v1/news?type=blog')->json('data'), 'type'))));
        $this->getJson('/api/v1/news/coming-next-week')->assertNotFound();
        $this->getJson('/api/v1/news/why-up-skilling-matters-in-every-profession')->assertNotFound();

        $post = $this->getJson('/api/v1/news/membership-registration-is-open')->assertOk()->json('data');
        $this->assertStringContainsString('<h2>', $post['body_html']);
        $this->assertCount(2, $post['related']);
        $this->assertGreaterThanOrEqual(1, $post['reading_minutes']);

        $this->travelTo('2026-11-01 09:00');
        $this->getJson('/api/v1/news/coming-next-week')->assertOk();

        // Twelve to a page.
        foreach (range(1, 12) as $i) {
            Post::query()->create(['type' => 'news', 'title' => "Update {$i}", 'body' => 'Text.']);
        }
        $this->assertSame(2, $this->getJson('/api/v1/news')->json('meta.last_page'));
        $this->assertCount(4, $this->getJson('/api/v1/news?page=2')->json('data'));
    }

    public function test_seminar_block_and_page(): void
    {
        $seminars = $this->sections()['seminars']['items'];
        $this->assertCount(1, $seminars);
        $this->assertSame(['test-seminar', '#enquiry', 'Reserve a seat'], [$seminars[0]['slug'], $seminars[0]['link'], $seminars[0]['button_text']]);
        $this->assertStringEndsWith('/media/menu/more-events.webp', $seminars[0]['image_url']);

        $page = $this->getJson('/api/v1/seminars/test-seminar')->assertOk()->json('data');
        $this->assertStringContainsString('<strong>test seminar</strong>', $page['description_html']);
        $this->assertSame([], $page['others']);

        Seminar::query()->where('slug', 'test-seminar')->update(['is_active' => false]);
        $this->getJson('/api/v1/seminars/test-seminar')->assertNotFound();
        $this->assertArrayNotHasKey('seminars', $this->sections()->all(), 'an empty block is left out');
    }

    public function test_board_and_member_organisation_blocks(): void
    {
        $s = $this->sections();

        $board = $s['board'];
        $this->assertSame(TeamMember::query()->active()->count(), count($board['items']));
        $this->assertSame('director-council', $board['items'][0]['group']);
        $this->assertSame(['key' => 'director-council', 'title' => 'Director Council', 'path' => '/management/director-council'], $board['extra']['groups'][0]);
        $this->assertStringContainsString('<li>', collect($board['items'])->firstWhere('name', 'Mr. Chandan Roy')['bio_html']);

        $this->assertCount(4, $s['corporate_members']['items']);
        $this->assertCount(6, $s['institutions']['items']);
        $this->assertSame('Sample University', $s['institutions']['items'][0]['name']);

        // Featured members come first; switched-off ones disappear.
        MemberOrganisation::query()->where('name', 'Sample Polytechnic')->update(['is_featured' => true]);
        MemberOrganisation::query()->where('name', 'Sample Public School')->update(['is_active' => false]);
        $institutions = $this->sections()['institutions']['items'];
        $this->assertSame('Sample Polytechnic', $institutions[0]['name']);
        $this->assertNotContains('Sample Public School', array_column($institutions, 'name'));
    }

    // ------------------------------------------------------------------ admin

    public function test_admin_crud_for_seminars_posts_and_organisations(): void
    {
        Storage::fake('public');
        $this->travelBack(); // Livewire treats uploads made "in the past" of a travelled clock as expired
        $this->actingAs(User::factory()->admin()->create());

        // The featured image is required.
        Livewire::test(Edit::class, ['resource' => 'seminars'])
            ->set('data.title', 'Leadership in practice')
            ->call('save')
            ->assertHasErrors(['uploads.image']);

        Livewire::test(Edit::class, ['resource' => 'seminars'])
            ->set('data.title', 'Leadership in practice')
            ->set('uploads.image', UploadedFile::fake()->image('banner.jpg', 1600, 800))
            ->set('data.link', 'https://example.org/register')
            ->set('data.description', "## Agenda\n\n- **Keynote**")
            ->call('save')->assertHasNoErrors();
        $seminar = Seminar::query()->where('slug', 'leadership-in-practice')->firstOrFail();
        Storage::disk('public')->assertExists($seminar->image);
        $detail = $this->getJson('/api/v1/seminars/leadership-in-practice')->json('data');
        $this->assertSame('https://example.org/register', $detail['link']);
        $this->assertStringContainsString('<strong>Keynote</strong>', $detail['description_html']);

        Livewire::test(Edit::class, ['resource' => 'news'])
            ->set('data.type', 'article')
            ->set('data.title', 'A new article')
            ->set('data.body', 'Some **text**.')
            ->call('save')->assertHasNoErrors();
        $this->assertSame('article', Post::query()->where('slug', 'a-new-article')->value('type'));

        Livewire::test(Edit::class, ['resource' => 'corporate-members'])
            ->set('data.name', 'Acme Consulting')
            ->set('data.category', 'Consulting')
            ->call('save')->assertHasNoErrors();
        $this->assertSame('corporate', MemberOrganisation::query()->where('name', 'Acme Consulting')->value('kind'));

        // Organisations of one kind cannot be opened through the other screen.
        $institution = MemberOrganisation::query()->kind('institutional')->firstOrFail();
        $this->get("/admin/manage/institutional-members/{$institution->id}/edit")->assertOk();
        $this->get("/admin/manage/corporate-members/{$institution->id}/edit")->assertNotFound();
        $this->get('/admin/manage/corporate-members')->assertOk()->assertSee('Acme Consulting')->assertDontSee('Sample University');
    }

    public function test_markdown_toolbar_is_shown_to_managers_only(): void
    {
        $post = Post::query()->firstOrFail();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get("/admin/manage/news/{$post->id}/edit")->assertOk()->assertSee('aria-label="Formatting"', false);

        $this->actingAs($admin);
        Livewire::test(Permissions::class)->set('matrix.user.news', '1')->call('save')->assertHasNoErrors();
        $member = User::factory()->create();
        $this->actingAs($member)->get("/admin/manage/news/{$post->id}/edit")->assertOk()->assertSee('View only')->assertDontSee('aria-label="Formatting"', false);
    }

    public function test_access_areas_of_the_new_modules(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();
        $member = User::factory()->create();

        foreach (['corporate-members', 'institutional-members', 'news', 'seminars'] as $area) {
            $this->assertSame(Access::MANAGE, Access::level($editor, $area), $area);
            $this->assertSame(Access::NONE, Access::level($member, $area), $area);
            $this->actingAs($member)->get("/admin/manage/{$area}")->assertForbidden();
            $this->actingAs($editor)->get("/admin/manage/{$area}")->assertOk();
        }
        $this->actingAs($admin)->get('/admin')->assertSee('News &amp; seminars', false)->assertSee('Corporate members')->assertSee('Institutional members');

        // Editors lose seminars; users get to see corporate members.
        $this->actingAs($admin);
        Livewire::test(Permissions::class)
            ->set('matrix.editor.seminars', '0')
            ->set('matrix.user.corporate-members', '1')
            ->call('save')->assertHasNoErrors();

        $this->actingAs($editor)->get('/admin/manage/seminars')->assertForbidden();
        $this->actingAs($editor)->get('/admin')->assertDontSee('/admin/manage/seminars', false);
        $this->actingAs($member)->get('/admin/manage/corporate-members')->assertOk()->assertSee('View only');
        $this->actingAs($member)->get('/admin/manage/corporate-members/create')->assertForbidden();
        $this->actingAs($member)->get('/admin/manage/institutional-members')->assertForbidden();
    }

    // ------------------------------------------------------------------ dashboard

    public function test_dashboard_figures(): void
    {
        $this->assertSame('membership', Metrics::kindOf('Membership registration'));
        $this->assertSame('registration', Metrics::kindOf('Event: IIP Leadership Summit 2026'));
        $this->assertSame('training', Metrics::kindOf('Training: Skill Development'));
        $this->assertSame('question', Metrics::kindOf('Question about Hunar'));
        $this->assertSame('general', Metrics::kindOf('Hello'));
        $this->assertSame('Skill Development', Metrics::topicOf('Registration: Skill Development · 16 Nov – 11 Dec 2026 (SD-01)'));

        foreach (['Membership registration', 'Event: Summit', 'Event: Summit', 'Hello'] as $i => $subject) {
            Enquiry::query()->create(['name' => "P{$i}", 'email' => "p{$i}@example.org", 'subject' => $subject, 'message' => 'Hi'])
                ->forceFill(['created_at' => now()->subDays($i)])->save();
        }
        $kinds = Metrics::enquiriesByKind();
        $this->assertSame([1, 2, 1], [$kinds['membership'], $kinds['registration'], $kinds['general']]);
        $this->assertSame(['Summit' => 2], Metrics::topRegistrations()->all());
        $days = Metrics::enquiriesPerDay(7);
        $this->assertCount(7, $days);
        $this->assertSame(3, array_sum(array_column($days, 'registrations')));
        $this->assertSame(1, array_sum(array_column($days, 'other')));

        $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk()
            ->assertSee('wire:poll.30s.visible', false)
            ->assertSee('Enquiries')
            ->assertSee('Summit');
    }
}
