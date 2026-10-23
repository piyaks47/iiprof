<?php

use App\Models\HomeSection;
use App\Models\Page;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Support\IiprofContent;
use App\Support\SiteSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * The iiprof.com images that were not used yet, in the places iiprof.com shows them:
 * sign-in logo + background, Highlights and Contact backgrounds, the About Us photos (in the text, with
 * the default banner on top as on iiprof.com), the credibility documents and Deepali Fialoke's video.
 * Only fills what is empty or still the shipped text — edits made in the admin are kept.
 */
return new class extends Migration
{
    /** md5 of the shipped texts these pages had before. */
    private const PREVIOUS = [
        'about-us' => 'c4fe3d12fa78114afbafcc0c705742c8',
        'credibility' => '4eb28566d49ed5215e0050626256c910',
    ];

    public function up(): void
    {
        try {
            $settings = IiprofContent::settings();
            foreach (['login_logo', 'login_background'] as $key) {
                $row = Setting::query()->firstOrCreate(['key' => $key]);
                if (blank($row->value)) {
                    $row->update(['value' => $settings[$key]]);
                }
            }

            $sections = IiprofContent::sections();
            foreach (['highlights', 'contact'] as $key) {
                $section = HomeSection::query()->where('key', $key)->first();
                if ($section && blank($section->image)) {
                    $section->forceFill(['image' => $sections[$key]['image']])->saveQuietly();
                }
            }

            $pages = IiprofContent::pages();
            foreach (self::PREVIOUS as $slug => $hash) {
                $page = Page::query()->where('slug', $slug)->first();
                if ($page && md5(trim((string) $page->content)) === $hash) {
                    $page->forceFill(['content' => $pages[$slug]['content']])->saveQuietly();
                }
            }
            // About Us: the seedling photo moves from the banner into the text (banner = default, as on iiprof.com).
            $about = Page::query()->where('slug', 'about-us')->first();
            if ($about && preg_match('~(^|/)imp-3(-[0-9a-f]{6})?\.jpe?g$~', (string) $about->featured_image)) {
                $about->forceFill(['featured_image' => null])->saveQuietly();
            }

            $video = collect(IiprofContent::testimonials())->firstWhere(0, 'Deepali Fialoke')[1] ?? null;
            Testimonial::query()->where('name', 'Deepali Fialoke')->whereNull('video_url')->update(['video_url' => $video]);
            Testimonial::query()->where('name', 'Deepali Fialoke')->where('video_url', '')->update(['video_url' => $video]);

            Cache::forget(SiteSettings::CACHE_KEY);
        } catch (Throwable $e) {
            Log::warning('Placing the remaining iiprof.com images postponed: '.$e->getMessage());
        }
    }

    public function down(): void {}
};
