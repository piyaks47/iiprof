<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Setting;
use App\Support\DemoContent;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_install_has_upcoming_sample_events_before_the_rest(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->travelTo('2026-10-25');
        DemoContent::orderByDate();

        $slugs = Event::query()->ordered()->pluck('slug')->all();
        $this->assertSame([
            'leadership-summit', 'hunar-career-fair', 'khazana-msme-finance-clinic', 'health-wellness-camp',
            'skill-india-webinar', 'csr-philanthropy-roundtable', 'scholarship-test',
        ], array_slice($slugs, 0, 7));
        // …then the past events made from the gallery photos.
        $this->assertSame(4, Event::query()->whereDate('starts_on', '<', '2026-10-25')->count());

        $this->getJson('/api/v1/events/hunar-career-fair')
            ->assertOk()
            ->assertJsonPath('data.location', 'Kolkata')
            ->assertJsonPath('data.button_url', '#enquiry')
            ->assertJsonPath('data.image_url', rtrim((string) config('app.url'), '/').'/media/events/hunar-career-fair.jpg');

        $sections = collect($this->getJson('/api/v1/home')->assertOk()->json('data.sections'));
        $this->assertCount(7, $sections->firstWhere('key', 'events')['items']);
    }

    public function test_sample_events_are_added_once_with_their_bundled_covers(): void
    {
        Storage::fake('public');
        Event::query()->delete();
        Setting::query()->where('key', DemoContent::MARKER)->delete();

        $this->assertSame(6, DemoContent::addEventsOnce());
        $this->assertSame('/media/events/leadership-summit.jpg', Event::query()->where('slug', 'leadership-summit')->value('image'));
        $this->assertFileExists(public_path('media/events/leadership-summit.jpg'));

        // Deleted in the admin → stays deleted, and the bundled cover is not removed.
        Event::query()->where('slug', 'leadership-summit')->first()->delete();
        $this->assertFileExists(public_path('media/events/leadership-summit.jpg'));
        $this->assertSame(0, DemoContent::addEventsOnce());
        $this->assertSame(5, Event::query()->count());
    }

    public function test_covers_copied_by_an_earlier_version_switch_to_the_bundled_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('events/demo/hunar-career-fair.jpg', 'old copy');
        Event::query()->create(['title' => 'Career fair', 'slug' => 'career-fair-old', 'image' => 'events/demo/hunar-career-fair.jpg']);
        Event::query()->create(['title' => 'Lost cover', 'slug' => 'lost', 'image' => 'events/demo/missing.jpg']);

        $this->assertSame(1, DemoContent::useBundledCovers());
        $this->assertSame('/media/events/hunar-career-fair.jpg', Event::query()->where('slug', 'career-fair-old')->value('image'));
        $this->assertSame('events/demo/missing.jpg', Event::query()->where('slug', 'lost')->value('image'));
        Storage::disk('public')->assertMissing('events/demo/hunar-career-fair.jpg');
    }

    public function test_an_existing_event_with_the_same_address_is_not_touched(): void
    {
        Event::query()->delete();
        Setting::query()->where('key', DemoContent::MARKER)->delete();
        Event::query()->create(['title' => 'Our own summit', 'slug' => 'leadership-summit']);

        $this->assertSame(5, DemoContent::addEventsOnce());
        $this->assertSame('Our own summit', Event::query()->where('slug', 'leadership-summit')->value('title'));
    }
}
