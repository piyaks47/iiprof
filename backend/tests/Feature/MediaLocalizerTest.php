<?php

namespace Tests\Feature;

use App\Models\GalleryItem;
use App\Models\HomeSection;
use App\Models\Page;
use App\Models\Setting;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Support\Media\MediaLocalizer;
use App\Support\SiteSettings;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaLocalizerTest extends TestCase
{
    use RefreshDatabase;

    private string $png;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');
        $this->png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
    }

    private function fakeIiprof(): void
    {
        Http::fake([
            'iiprof.com/*.pdf' => Http::response('%PDF-1.4 test', 200, ['Content-Type' => 'application/pdf']),
            'iiprof.com/*.PDF' => Http::response('%PDF-1.4 test', 200, ['Content-Type' => 'application/pdf']),
            'iiprof.com/*.mp4' => Http::response('mp4-bytes', 200, ['Content-Type' => 'video/mp4']),
            'iiprof.com/*' => Http::response($this->png, 200, ['Content-Type' => 'image/png']),
        ]);
    }

    public function test_every_iiprof_com_image_has_a_place_on_the_site(): void
    {
        $settings = app(SiteSettings::class);
        $this->assertStringEndsWith('img/logo.png', $settings->get('login_logo'));
        $this->assertStringEndsWith('img/bg-login.jpg', $settings->get('login_background'));
        $this->assertStringEndsWith('img/banner-bg.jpg', HomeSection::query()->where('key', 'highlights')->value('image'));
        $this->assertStringEndsWith('img/home-bg.jpg', HomeSection::query()->where('key', 'contact')->value('image'));

        $about = Page::query()->where('slug', 'about-us')->firstOrFail();
        $this->assertNull($about->featured_image, 'About Us uses the default banner, as on iiprof.com');
        foreach (['img/imp-3.jpg', 'files/hands-1917895.png', 'img/about-img-3.jpg'] as $photo) {
            $this->assertStringContainsString($photo, $about->content);
        }
        $this->assertSame(15, substr_count(Page::query()->where('slug', 'credibility')->value('content'), '[Download]('));
        $this->assertStringEndsWith('.mp4', Testimonial::query()->where('name', 'Deepali Fialoke')->value('video_url'));

        $home = collect($this->getJson('/api/v1/home')->json('data.sections'))->keyBy('key');
        $this->assertStringEndsWith('home-bg.jpg', $home['contact']['image_url']);
        $this->assertNotNull(collect($this->getJson('/api/v1/testimonials')->json('data'))->firstWhere('name', 'Deepali Fialoke')['video_file_url']);
    }

    public function test_copies_images_documents_and_videos_and_records_where_they_came_from(): void
    {
        $this->fakeIiprof();

        $this->artisan('iiprof:localize-images')->assertSuccessful();

        $settings = app(SiteSettings::class);
        foreach (['logo', 'default_banner', 'login_logo', 'login_background'] as $key) {
            $this->assertStringStartsWith('imports/', $settings->get($key), $key);
            Storage::disk('public')->assertExists($settings->get($key));
        }
        $contact = HomeSection::query()->where('key', 'contact')->value('image');
        Storage::disk('public')->assertExists($contact);

        $credibility = Page::query()->where('slug', 'credibility')->value('content');
        $this->assertStringNotContainsString('iiprof.com', $credibility);
        $this->assertMatchesRegularExpression('~\(/storage/imports/iip-80g-certificate-[0-9a-f]{6}\.pdf\)~', $credibility);

        $video = Testimonial::query()->where('name', 'Deepali Fialoke')->value('video_url');
        $this->assertMatchesRegularExpression('~^/storage/imports/suraj-jhunjhunwala-1649770957-[0-9a-f]{6}\.mp4$~', $video);
        Storage::disk('public')->assertExists(substr($video, strlen('/storage/')));

        $this->assertSame(1, DB::table('imported_files')->where('path', $contact)->count());
        $this->assertStringEndsWith('img/home-bg.jpg', DB::table('imported_files')->where('path', $contact)->value('source_url'));

        // A second run downloads nothing.
        Http::fake(['*' => fn () => throw new \RuntimeException('should not download again')]);
        $report = (new MediaLocalizer)->run();
        $this->assertSame(0, $report['copied'] + $report['repaired'] + $report['failed']);
    }

    public function test_missing_copies_are_fetched_again_even_from_older_versions(): void
    {
        $this->fakeIiprof();

        // State of a site whose copies were made by an earlier version and then went missing:
        // the content points at imports/… but the files are gone and nothing records where they came from.
        $logo = 'imports/'.MediaLocalizer::baseName('https://iiprof.com/public/img/logo-black.png').'.png';
        $photo = 'imports/'.MediaLocalizer::baseName('https://iiprof.com/public/uploads/team/thumb/Mr.%20Chandan%20Roy__1679908912.jpg').'.jpg';
        $gallery = 'imports/'.MediaLocalizer::baseName('http://iiprof.com/public/uploads/gallery/original/gallery_1678270426.jpeg').'.jpg';
        Setting::query()->where('key', 'logo')->update(['value' => $logo]);
        TeamMember::query()->where('name', 'Mr. Chandan Roy')->update(['photo' => $photo]);
        GalleryItem::query()->where('image', 'like', '%gallery_1678270426%')->update(['image' => $gallery]);
        Page::query()->where('slug', 'how-it-works')->update(['content' => '![Step](/storage/imports/'.MediaLocalizer::baseName('https://iiprof.com/public/img/howpic1.jpg').'.jpg)']);
        GalleryItem::query()->where('image', 'like', '%gallery_1677072224%')->update(['image' => 'imports/unknown-abcdef.jpg']);
        app(SiteSettings::class)->set([]); // clear the cache

        $report = (new MediaLocalizer)->run();

        foreach ([$logo, $photo, $gallery, 'imports/'.MediaLocalizer::baseName('https://iiprof.com/public/img/howpic1.jpg').'.jpg'] as $path) {
            Storage::disk('public')->assertExists($path);
        }
        $this->assertSame($logo, app(SiteSettings::class)->get('logo'), 'content keeps pointing at the same path');
        $this->assertSame(4, $report['repaired']);
        $this->assertSame(1, $report['unrecoverable']);

        // Recorded now: deleting the file again is repaired from the record.
        Storage::disk('public')->delete($photo);
        $this->assertSame(1, (new MediaLocalizer)->run()['repaired']);
        Storage::disk('public')->assertExists($photo);
    }

    public function test_a_missing_copy_that_cannot_be_fetched_shows_the_original_again(): void
    {
        Http::fake(['*' => Http::response('down', 503)]);
        $logo = 'imports/'.MediaLocalizer::baseName('https://iiprof.com/public/img/logo-black.png').'.png';
        $photo = 'imports/'.MediaLocalizer::baseName('https://iiprof.com/public/img/howpic1.jpg').'.jpg';
        Setting::query()->where('key', 'logo')->update(['value' => $logo]);
        Page::query()->where('slug', 'how-it-works')->update(['content' => '![Step](/storage/'.$photo.')']);
        app(SiteSettings::class)->set([]);

        (new MediaLocalizer)->run();

        $this->assertSame('https://iiprof.com/public/img/logo-black.png', app(SiteSettings::class)->get('logo'));
        $this->assertSame('![Step](https://iiprof.com/public/img/howpic1.jpg)', Page::query()->where('slug', 'how-it-works')->value('content'));
    }

    public function test_shared_copies_are_not_deleted_with_a_row(): void
    {
        $this->fakeIiprof();
        $this->artisan('iiprof:localize-images')->assertSuccessful();

        $photo = TeamMember::query()->where('group', 'governing-body')->value('photo');
        $this->assertStringStartsWith('imports/', $photo);
        TeamMember::query()->where('group', 'governing-body')->first()->delete();

        Storage::disk('public')->assertExists($photo);
    }

    public function test_very_large_photos_are_scaled_down(): void
    {
        $img = imagecreatetruecolor(3000, 2000);
        ob_start();
        imagejpeg($img, null, 90);
        $jpeg = (string) ob_get_clean();
        Http::fake(['iiprof.com/*' => Http::response($jpeg, 200, ['Content-Type' => 'image/jpeg'])]);

        $path = (new MediaLocalizer)->copy('https://iiprof.com/public/img/home-bg.jpg');

        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertSame([2400, 1600], [$w, $h]);
    }
}
