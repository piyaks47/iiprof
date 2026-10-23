<?php

namespace App\Support\Media;

use App\Models\Event;
use App\Models\GalleryItem;
use App\Models\HeroSlide;
use App\Models\HomeSection;
use App\Models\Page;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Support\IiprofContent;
use App\Support\SiteSettings;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Copies the images, documents and videos the content still loads from iiprof.com onto this server
 * (public disk, folder imports/) and points the content at the copies.
 *
 * - Every copy is written, then checked on disk before the content is changed.
 * - Where each copy came from is recorded (table imported_files), and copies that have gone missing
 *   are fetched again — also for installs from before that record existed (known iiprof.com files).
 * - Very large photos are scaled down (longest side 2400 px) when the GD extension is available.
 */
class MediaLocalizer
{
    public const HOSTS = ['iiprof.com', 'www.iiprof.com'];

    public const DIR = 'imports';

    private const TYPES = [
        'image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/pjpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
        'image/gif' => 'gif', 'image/svg+xml' => 'svg', 'application/pdf' => 'pdf', 'video/mp4' => 'mp4', 'video/webm' => 'webm',
    ];

    private const MAX_BYTES = 64 * 1024 * 1024;

    private const MAX_EDGE = 2400;

    /** Image columns (path on the public disk, or an absolute URL). */
    public const COLUMNS = [
        [HeroSlide::class, 'image'], [Event::class, 'image'], [GalleryItem::class, 'image'], [HomeSection::class, 'image'],
        [TeamMember::class, 'photo'], [Testimonial::class, 'photo'], [Page::class, 'featured_image'],
        [\App\Models\FeaturedMember::class, 'photo'], [\App\Models\HomeItem::class, 'image'],
        [\App\Models\TrainingProgramme::class, 'image'], [\App\Models\MenuItem::class, 'image'],
    ];

    /** Linked files inside page texts that are copied too. */
    private const LINKED = 'jpe?g|png|gif|webp|svg|pdf|mp4|webm';

    /** @var array<string, ?string> source URL => local path (this run) */
    private array $done = [];

    private array $report = ['copied' => 0, 'repaired' => 0, 'failed' => 0, 'unrecoverable' => 0];

    /** @var array<string, string>|null legacy file name (without extension) => source URL */
    private ?array $legacy = null;

    public function __construct(private ?Closure $log = null) {}

    /** @return array{copied: int, repaired: int, failed: int, unrecoverable: int} */
    public function run(): array
    {
        $this->repairMissing();
        $this->localizeColumns();
        $this->localizePageTexts();
        $this->localizeSettings();
        $this->localizeVideos();

        return $this->report;
    }

    // ------------------------------------------------------------------
    // Remote → local
    // ------------------------------------------------------------------

    private function localizeColumns(): void
    {
        foreach (self::COLUMNS as [$model, $column]) {
            $model::query()->where($column, 'like', 'http%')->get()->each(function ($row) use ($column) {
                if ($path = $this->copy((string) $row->getAttribute($column))) {
                    $row->forceFill([$column => $path])->saveQuietly();
                }
            });
        }
    }

    private function localizePageTexts(): void
    {
        $pattern = '~\((https?://(?:www\.)?iiprof\.com/[^)\s]+\.(?:'.self::LINKED.'))\)~i';

        Page::query()->where('content', 'like', '%iiprof.com/%')->get()->each(function (Page $page) use ($pattern) {
            $content = preg_replace_callback($pattern, function ($m) {
                $path = $this->copy($m[1]);

                return $path ? '('.self::publicPath($path).')' : $m[0];
            }, (string) $page->content);

            if ($content !== $page->content) {
                $page->forceFill(['content' => $content])->saveQuietly();
            }
        });
    }

    private function localizeSettings(): void
    {
        $settings = app(SiteSettings::class);
        foreach (SiteSettings::FILE_KEYS as $key) {
            $value = $settings->get($key);
            if ($value && preg_match('~^https?://~i', $value) && ($path = $this->copy($value))) {
                $settings->set([$key => $path]);
            }
        }
    }

    /** Testimonial videos that are files (not YouTube) become /storage/… links. */
    private function localizeVideos(): void
    {
        Testimonial::query()->where('video_url', 'like', 'http%')->get()->each(function (Testimonial $t) {
            if (preg_match('~\.(mp4|webm)(\?|$)~i', (string) $t->video_url) && ($path = $this->copy((string) $t->video_url))) {
                $t->forceFill(['video_url' => self::publicPath($path)])->saveQuietly();
            }
        });
    }

    // ------------------------------------------------------------------
    // Missing local copies → fetch again
    // ------------------------------------------------------------------

    private function repairMissing(): void
    {
        $paths = [];
        foreach (self::COLUMNS as [$model, $column]) {
            foreach ($model::query()->where($column, 'like', self::DIR.'/%')->pluck($column) as $path) {
                $paths[] = $path;
            }
        }
        foreach (Page::query()->where('content', 'like', '%/storage/'.self::DIR.'/%')->pluck('content') as $content) {
            preg_match_all('~/storage/('.self::DIR.'/[^)\s"\']+)~', (string) $content, $m);
            array_push($paths, ...$m[1]);
        }
        foreach (Testimonial::query()->where('video_url', 'like', '%/storage/'.self::DIR.'/%')->pluck('video_url') as $url) {
            $paths[] = Str::after((string) $url, '/storage/');
        }
        $settings = app(SiteSettings::class);
        foreach (SiteSettings::FILE_KEYS as $key) {
            $value = (string) $settings->get($key);
            if (str_starts_with($value, self::DIR.'/')) {
                $paths[] = $value;
            }
        }

        $disk = Storage::disk('public');
        foreach (array_unique(array_map('rawurldecode', $paths)) as $path) {
            if ($disk->exists($path)) {
                continue;
            }
            $source = $this->sourceOf($path);
            if (! $source) {
                $this->report['unrecoverable']++;
                $this->say("Missing {$path} — its original address is unknown (upload it again in the admin).");

                continue;
            }
            if ($this->download($source, $path)) {
                $this->report['repaired']++;
                $this->say("Fetched again: {$path}");
            } else {
                // Could not fetch it now: show the original again (and try again on the next run).
                $this->done[$source] = null;
                $this->pointBack($path, $source);
            }
        }
    }

    /** Replaces every reference to a missing local copy with its original address. */
    private function pointBack(string $path, string $source): void
    {
        foreach (self::COLUMNS as [$model, $column]) {
            $model::query()->where($column, $path)->get()->each(fn ($row) => $row->forceFill([$column => $source])->saveQuietly());
        }
        $local = self::publicPath($path);
        Page::query()->where('content', 'like', '%'.$local.'%')->get()->each(function (Page $page) use ($local, $source) {
            $page->forceFill(['content' => str_replace($local, $source, (string) $page->content)])->saveQuietly();
        });
        Testimonial::query()->where('video_url', $local)->update(['video_url' => $source]);
        $settings = app(SiteSettings::class);
        foreach (SiteSettings::FILE_KEYS as $key) {
            if ($settings->get($key) === $path) {
                $settings->set([$key => $source]);
            }
        }
        $this->say("Showing {$path} from its original address until it can be copied: {$source}");
    }

    /** Original address of a local copy: from the record, or by its file name (copies made by earlier versions). */
    public function sourceOf(string $path): ?string
    {
        if ($this->hasTable()) {
            $url = DB::table('imported_files')->where('path', $path)->value('source_url');
            if ($url) {
                return $url;
            }
        }

        if ($this->legacy === null) {
            $this->legacy = [];
            foreach (IiprofContent::mediaUrls() as $url) {
                foreach (self::variants($url) as $variant) {
                    $this->legacy[self::baseName($variant)] = $url;
                }
            }
        }

        return $this->legacy[pathinfo($path, PATHINFO_FILENAME)] ?? null;
    }

    // ------------------------------------------------------------------
    // Download
    // ------------------------------------------------------------------

    /** Local path of the copy of $url (made now if needed), or null when it cannot be fetched. */
    public function copy(string $url): ?string
    {
        if (! in_array(strtolower((string) parse_url($url, PHP_URL_HOST)), self::HOSTS, true)) {
            return null;
        }
        if (array_key_exists($url, $this->done)) {
            return $this->done[$url];
        }

        // Copied before and still there?
        if ($this->hasTable()) {
            $known = DB::table('imported_files')->where('source_hash', md5($url))->value('path');
            if ($known && Storage::disk('public')->exists($known)) {
                return $this->done[$url] = $known;
            }
        }

        $path = $this->download($url);
        if ($path) {
            $this->report['copied']++;
        }

        return $this->done[$url] = $path;
    }

    /** Downloads $url to $target (or a new imports/ path); records it; null on failure. */
    private function download(string $url, ?string $target = null): ?string
    {
        $last = 'no answer';
        foreach (array_unique([preg_replace('~^http://~i', 'https://', $url), $url]) as $candidate) {
            try {
                $response = Http::timeout(120)->connectTimeout(10)->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; IIP-site/1.0)'])->get($candidate);
            } catch (Throwable $e) {
                $last = Str::limit($e->getMessage(), 100);

                continue;
            }
            if (! $response->successful()) {
                $last = 'HTTP '.$response->status();

                continue;
            }

            $body = $response->body();
            $mime = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
            $ext = self::TYPES[$mime] ?? self::extensionFromUrl($candidate);
            if (! $ext || $body === '' || strlen($body) > self::MAX_BYTES) {
                $last = $ext ? 'empty or too large' : 'not an image, PDF or video ('.($mime ?: 'unknown type').')';

                continue;
            }

            [$body, $ext] = $this->optimize($body, $ext, keepFormat: $target !== null);
            $path = $target ?? self::DIR.'/'.self::baseName($url).'.'.$ext;

            if (! $this->write($path, $body)) {
                $last = 'could not save it in storage/app/public/'.self::DIR.' (folder not writable?)';

                break;
            }

            $this->record($path, $url, self::mimeFor($ext), strlen($body));

            return $path;
        }

        $this->report['failed']++;
        $this->say("Could not copy {$url} ({$last})");

        return null;
    }

    private function write(string $path, string $body): bool
    {
        $disk = Storage::disk('public');
        try {
            $disk->put($path, $body);
        } catch (Throwable) {
            return false;
        }

        return $disk->exists($path) && $disk->size($path) === strlen($body);
    }

    private function record(string $path, string $url, string $mime, int $bytes): void
    {
        if (! $this->hasTable()) {
            return;
        }
        DB::table('imported_files')->updateOrInsert(['path' => $path], [
            'source_hash' => md5($url), 'source_url' => $url, 'mime' => $mime, 'bytes' => $bytes,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /**
     * Scales very large photos down (GD). Transparent PNGs become WebP when possible.
     *
     * @return array{0: string, 1: string} body, extension
     */
    private function optimize(string $body, string $ext, bool $keepFormat): array
    {
        if (! in_array($ext, ['jpg', 'png', 'webp'], true) || ! function_exists('imagecreatefromstring')) {
            return [$body, $ext];
        }
        $size = @getimagesizefromstring($body);
        if (! $size) {
            return [$body, $ext];
        }
        [$w, $h] = $size;
        $edge = max($w, $h);
        if ($edge <= self::MAX_EDGE && strlen($body) <= 3 * 1024 * 1024) {
            return [$body, $ext];
        }
        // Decoding needs ~5 bytes per pixel; skip when the server's memory limit is too low.
        if (! $this->enoughMemory($w * $h * 5 + 16 * 1024 * 1024)) {
            return [$body, $ext];
        }

        try {
            $src = @imagecreatefromstring($body);
            if (! $src) {
                return [$body, $ext];
            }
            $scale = min(1, self::MAX_EDGE / $edge);
            $nw = max(1, (int) round($w * $scale));
            $nh = max(1, (int) round($h * $scale));
            $dst = imagecreatetruecolor($nw, $nh);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($src);

            $out = $ext;
            if (! $keepFormat && $ext === 'png' && function_exists('imagewebp')) {
                $out = 'webp';
            }
            ob_start();
            $ok = match ($out) {
                'jpg' => imagejpeg($dst, null, 85),
                'webp' => imagewebp($dst, null, 85),
                default => imagepng($dst, null, 8),
            };
            $data = (string) ob_get_clean();
            imagedestroy($dst);

            return $ok && $data !== '' && strlen($data) < strlen($body) ? [$data, $out] : [$body, $ext];
        } catch (Throwable) {
            return [$body, $ext];
        }
    }

    private function enoughMemory(int $needed): bool
    {
        $free = fn () => ($limit = self::bytes((string) ini_get('memory_limit'))) < 0 ? PHP_INT_MAX : $limit - memory_get_usage(true);
        if ($free() >= $needed) {
            return true;
        }
        @ini_set('memory_limit', (string) (memory_get_usage(true) + $needed + 32 * 1024 * 1024));

        return $free() >= $needed;
    }

    private static function bytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return -1;
        }

        return (int) $value * match (strtolower(substr($value, -1))) { 'g' => 1024 ** 3, 'm' => 1024 ** 2, 'k' => 1024, default => 1 };
    }

    // ------------------------------------------------------------------

    /** imports/<name>-<hash>: the naming used since the first version (kept, so old copies can be matched). */
    public static function baseName(string $url): string
    {
        $base = Str::slug(pathinfo(rawurldecode((string) parse_url($url, PHP_URL_PATH)), PATHINFO_FILENAME)) ?: 'file';

        return $base.'-'.substr(md5($url), 0, 6);
    }

    /** Spellings an address may have been stored with (http/https, spaces/%20). */
    private static function variants(string $url): array
    {
        $out = [];
        foreach ([$url, str_replace('%20', ' ', $url), str_replace(' ', '%20', $url)] as $u) {
            $out[] = $u;
            $out[] = preg_replace('~^https://~i', 'http://', $u);
            $out[] = preg_replace('~^http://~i', 'https://', $u);
            $out[] = preg_replace('~://iiprof\.com/~i', '://www.iiprof.com/', $u);
        }

        return array_values(array_unique($out));
    }

    /** Site-relative address of a file on the public disk: /storage/imports/… */
    public static function publicPath(string $path): string
    {
        $url = Storage::disk('public')->url($path);

        return (string) (parse_url($url, PHP_URL_PATH) ?: $url);
    }

    private static function extensionFromUrl(string $url): ?string
    {
        $ext = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        $ext = $ext === 'jpeg' ? 'jpg' : $ext;

        return in_array($ext, ['jpg', 'png', 'gif', 'webp', 'svg', 'pdf', 'mp4', 'webm'], true) ? $ext : null;
    }

    private static function mimeFor(string $ext): string
    {
        return array_search($ext, self::TYPES, true) ?: 'application/octet-stream';
    }

    private ?bool $table = null;

    private function hasTable(): bool
    {
        return $this->table ??= Schema::hasTable('imported_files');
    }

    private function say(string $line): void
    {
        $this->log && ($this->log)($line);
    }
}
