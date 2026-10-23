<?php

namespace App\Support\Import;

use App\Models\FooterLink;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Support\IiprofContent;
use App\Support\IiprofPages;
use App\Support\Media\MediaLocalizer;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Copies page texts from a web page — by default the same address on iiprof.com — into this CMS.
 *
 * Reads the page title (".page-title-area h1"), the page body ("section.cms-area .container",
 * testimonials in "section.alumini-area") and, on management pages, the people with their
 * biographies. Forms, pop-ups and scripts are left out; links and images become absolute URLs
 * (run `php artisan iiprof:localize-images` afterwards to copy the images to this server).
 */
class PageImporter
{
    public const SOURCE = 'https://iiprof.com';

    /** Our address → iiprof.com address, where they differ. */
    public const RENAMED = [
        '/industry/it-ites' => '/industry/it-&-ites',
    ];

    /** Pages whose content the website builds itself (events, gallery) or that iiprof.com does not have. */
    public const SKIP = ['/', '/view-events', '/view-gallery', '/edu10', '/talent-register'];

    public function sourceUrl(string $path): string
    {
        $path = '/'.trim($path, '/');
        $path = self::RENAMED[$path] ?? $path;

        // iiprof.com spells the management pages with capitals: /management/Director-Council.
        if (preg_match('~^/management/([a-z-]+)$~', $path, $m)) {
            $path = '/management/'.implode('-', array_map('ucfirst', explode('-', $m[1])));
            $path = str_replace('-Of-', '-of-', $path);
        }

        return self::SOURCE.$path;
    }

    /** @return array{title: ?string, content: string, team: list<array>, testimonials: list<array>, url: string} */
    public function fetch(string $url): array
    {
        if (! preg_match('~^https?://~i', $url)) {
            throw new RuntimeException('Enter a full web address starting with https://');
        }
        $this->assertPublicHost((string) parse_url($url, PHP_URL_HOST));

        try {
            $response = Http::timeout(25)->connectTimeout(10)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; IIP-site-importer/1.0)', 'Accept' => 'text/html'])
                ->get($url);
        } catch (\Throwable $e) {
            throw new RuntimeException('Could not reach '.$url.' — '.Str::limit($e->getMessage(), 120));
        }

        if ($response->status() === 404) {
            throw new RuntimeException('There is no page at '.$url.' (404).');
        }
        if (! $response->successful()) {
            throw new RuntimeException($url.' answered with HTTP '.$response->status().'.');
        }

        return $this->extract($response->body(), $url);
    }

    /** @return array{title: ?string, content: string, team: list<array>, testimonials: list<array>, url: string} */
    public function extract(string $html, string $url): array
    {
        $doc = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($doc);
        $markdown = new HtmlToMarkdown($url);

        $title = $this->text($this->first($xpath, [
            "//*[contains(concat(' ', normalize-space(@class), ' '), ' page-title-area ')]//h1",
            "//*[contains(concat(' ', normalize-space(@class), ' '), ' page-title-area ')]//h2",
            '//main//h1', '//h1', '//title',
        ]));

        $team = $this->team($xpath, $markdown);
        $testimonials = $this->testimonials($xpath, $url);

        $root = $this->first($xpath, [
            "//section[contains(concat(' ', normalize-space(@class), ' '), ' cms-area ')]//div[contains(concat(' ', normalize-space(@class), ' '), ' container ')]",
            "//section[contains(concat(' ', normalize-space(@class), ' '), ' cms-area ')]",
            '//main', '//article',
        ]);

        $content = '';
        if ($root && ! $team && ! $testimonials) {
            $content = MarkdownTidy::clean($markdown->convert($root));
            // The page title is shown in the banner — drop it when the text repeats it as its first heading.
            if ($title) {
                $content = preg_replace('/^#{2,4} '.preg_quote($title, '/').'\s*\n+/iu', '', $content) ?? $content;
            }
        }

        return ['title' => $title, 'content' => trim($content), 'team' => $team, 'testimonials' => $testimonials, 'url' => $url];
    }

    /**
     * Pulls the page for every menu / footer address of this site.
     *
     * @param  list<string>  $only  limit to these addresses
     * @param  callable(string $path, string $result): void|null  $report
     * @return array<string, string> address → what happened
     */
    public function pullSite(bool $force = false, array $only = [], ?callable $report = null): array
    {
        $results = [];
        $unreachable = 0;
        foreach ($only ?: $this->sitePaths() as $path) {
            $path = '/'.trim($path, '/');
            if (in_array($path, self::SKIP, true)) {
                continue;
            }
            try {
                $result = $this->pullPath($path, $force);
                $unreachable = -1000; // the site answers: never give up early
            } catch (\Throwable $e) {
                $result = 'failed: '.$e->getMessage();
                if (preg_match('/^(Could not reach|Could not find the website)/', $e->getMessage())) {
                    $unreachable++;
                }
            }
            $results[$path] = $result;
            $report && $report($path, $result);

            if ($unreachable >= 3) {
                $results['*'] = 'failed: stopped — '.parse_url(self::SOURCE, PHP_URL_HOST).' cannot be reached from this server';
                $report && $report('*', $results['*']);
                break;
            }
        }

        return $results;
    }

    /** Imports one address. Edited pages are kept unless $force. */
    public function pullPath(string $path, bool $force = false): string
    {
        $page = Page::forPath($path) ?? Page::query()->where('public_path', $path)->first()
            ?? Page::query()->where('slug', str_replace('/', '-', trim($path, '/')))->first();
        if (! $page) {
            return 'skipped (no page with this address — create it under Website → Pages)';
        }

        return $this->apply($page, $this->fetch($this->sourceUrl($path)), $force);
    }

    /**
     * Puts extracted data (see extract()) into a page: its text, or — on management and testimonial
     * pages — the people / testimonials. Edited texts are kept unless $force.
     *
     * @param  array{content: string, team: list<array>, testimonials: list<array>}  $data
     */
    public function apply(Page $page, array $data, bool $force = false): string
    {
        if ($data['team'] ?? []) {
            return $this->applyTeam(Str::after($page->slug, 'management-'), $data['team'], $force);
        }
        if ($data['testimonials'] ?? []) {
            return $this->applyTestimonials($data['testimonials'], $force);
        }
        if (($data['content'] ?? '') === '') {
            return 'nothing to copy (the page on iiprof.com has no text)';
        }
        if (! $force && ! $this->untouched($page)) {
            return 'kept (edited in the admin — use --force to overwrite)';
        }
        if (trim((string) $page->content) === $data['content']) {
            return 'already up to date';
        }

        $page->forceFill(['content' => $data['content']])->save();

        return 'copied ('.mb_strlen($data['content']).' characters)';
    }

    /**
     * Text is empty, or still a text this CMS shipped with (the starter summary, an earlier version of it,
     * or the iiprof.com original) — also after its images were copied to this server.
     */
    public function untouched(Page $page): bool
    {
        $content = trim((string) $page->content);
        if ($content === '') {
            return true;
        }

        $shipped = array_filter([
            IiprofContent::pages()[$page->slug]['content'] ?? null,
            IiprofPages::content($page->path()),
        ]);
        $candidates = array_unique([$content, $this->withOriginalImages($content)]);

        foreach ($candidates as $text) {
            foreach ($shipped as $original) {
                if ($text === trim($original)) {
                    return true;
                }
            }
            if (in_array(md5($text), IiprofContent::OLDER_PAGE_HASHES[$page->slug] ?? [], true)) {
                return true;
            }
        }

        return false;
    }

    /** /storage/imports/… copies in a text → the iiprof.com addresses they were copied from. */
    private function withOriginalImages(string $content): string
    {
        if (! str_contains($content, '/storage/'.MediaLocalizer::DIR.'/')) {
            return $content;
        }
        $localizer = new MediaLocalizer;

        return preg_replace_callback('~/storage/('.MediaLocalizer::DIR.'/[^)\s"\']+)~', function (array $m) use ($localizer) {
            return $localizer->sourceOf(rawurldecode($m[1])) ?? $m[0];
        }, $content) ?? $content;
    }

    /** @return list<string> internal addresses in the menu and the footer */
    public function sitePaths(): array
    {
        return MenuItem::query()->pluck('url')->merge(FooterLink::query()->pluck('url'))
            ->filter(fn ($url) => is_string($url) && str_starts_with($url, '/') && ! str_starts_with($url, '//'))
            ->map(fn ($url) => '/'.trim(strtok($url, '?#'), '/'))
            ->unique()->sort()->values()->all();
    }

    // ------------------------------------------------------------------

    /** @return list<array{name: string, role: ?string, photo: ?string, bio: ?string}> */
    private function team(DOMXPath $xpath, HtmlToMarkdown $markdown): array
    {
        $people = [];
        foreach ($xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' single-team-box ')]") ?: [] as $box) {
            $name = $this->text($xpath->query('.//h3', $box)->item(0));
            if (! $name) {
                continue;
            }
            $img = $xpath->query('.//img', $box)->item(0);
            $details = $xpath->query('.//*[@data-details]', $box)->item(0);
            $bio = null;
            if ($details instanceof DOMElement && trim($details->getAttribute('data-details')) !== '') {
                $fragment = new DOMDocument;
                $previous = libxml_use_internal_errors(true);
                $fragment->loadHTML('<?xml encoding="UTF-8"><div>'.$details->getAttribute('data-details').'</div>', LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET);
                libxml_clear_errors();
                libxml_use_internal_errors($previous);
                $bio = MarkdownTidy::clean($markdown->convert($fragment->getElementsByTagName('div')->item(0))) ?: null;
            }
            $people[] = [
                'name' => $name,
                'role' => $this->text($xpath->query(".//span[contains(concat(' ', normalize-space(@class), ' '), ' mt-1 ')]", $box)->item(0) ?? $xpath->query('.//span', $box)->item(0)),
                'photo' => $img instanceof DOMElement && $img->getAttribute('src') ? $markdown->url($img->getAttribute('src')) : null,
                'bio' => $bio,
            ];
        }

        return $people;
    }

    /** @return list<array{name: string, quote: ?string, photo: ?string, video: ?string}> */
    private function testimonials(DOMXPath $xpath, string $url): array
    {
        $items = [];
        foreach ($xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' alumini-item ')]") ?: [] as $item) {
            $name = $this->text($xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' alumini-name ')]", $item)->item(0));
            if (! $name) {
                continue;
            }
            $quote = $this->text($xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' alumini-description ')]//p", $item)->item(0));
            $img = $xpath->query('.//img', $item)->item(0);
            $iframe = $xpath->query('.//iframe[@src]', $item)->item(0);
            $video = $iframe instanceof DOMElement ? $iframe->getAttribute('src') : null;
            if ($video && preg_match('~youtube(?:-nocookie)?\.com/embed/([\w-]{6,})~', $video, $m)) {
                $video = 'https://www.youtube.com/watch?v='.$m[1];
            }
            $items[] = [
                'name' => $name,
                'quote' => $quote,
                'photo' => $img instanceof DOMElement && $img->getAttribute('src') ? HtmlToMarkdown::absolute($img->getAttribute('src'), $url) : null,
                'video' => $video,
            ];
        }

        return $items;
    }

    private function applyTeam(string $group, array $people, bool $force): string
    {
        $created = $updated = 0;
        foreach ($people as $person) {
            $member = TeamMember::query()->where('group', $group)->get()
                ->first(fn (TeamMember $m) => self::sameName($m->name, $person['name']));

            if (! $member) {
                TeamMember::query()->create(['group' => $group, 'name' => $person['name'], 'role' => $person['role'], 'bio' => $person['bio'], 'photo' => $person['photo']]);
                $created++;

                continue;
            }

            $changes = array_filter([
                'role' => ($force || blank($member->role)) ? $person['role'] : null,
                'bio' => ($force || blank($member->bio)) ? $person['bio'] : null,
                'photo' => blank($member->photo) ? $person['photo'] : null,
            ], fn ($v) => filled($v));
            if ($changes && $member->fill($changes)->isDirty()) {
                $member->save();
                $updated++;
            }
        }

        return "people: {$updated} updated, {$created} added";
    }

    private function applyTestimonials(array $items, bool $force): string
    {
        $created = $updated = 0;
        foreach ($items as $item) {
            $row = Testimonial::query()->get()->first(fn (Testimonial $t) => self::sameName($t->name, $item['name']));
            if (! $row) {
                Testimonial::query()->create(['name' => $item['name'], 'quote' => $item['quote'], 'photo' => $item['photo'], 'video_url' => $item['video']]);
                $created++;

                continue;
            }
            $changes = array_filter([
                'quote' => ($force || blank($row->quote)) ? $item['quote'] : null,
                'video_url' => blank($row->video_url) ? $item['video'] : null,
            ], fn ($v) => filled($v));
            if ($changes && $row->fill($changes)->isDirty()) {
                $row->save();
                $updated++;
            }
        }

        return "testimonials: {$updated} updated, {$created} added";
    }

    /** Only pages on the public internet (not this server or the local network). */
    private function assertPublicHost(string $host): void
    {
        $host = trim($host, '[]');
        if ($host === '' || strcasecmp($host, 'localhost') === 0 || str_ends_with(strtolower($host), '.local')) {
            throw new RuntimeException('That address is not a public web page.');
        }
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
        if ($ips === [] && ! app()->runningUnitTests()) {
            throw new RuntimeException('Could not find the website '.$host.'.');
        }
        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new RuntimeException('That address is not a public web page.');
            }
        }
    }

    private static function sameName(?string $a, ?string $b): bool
    {
        $norm = fn (?string $s) => preg_replace('/^(mr|mrs|ms|dr|prof)\.?\s+/i', '', Str::lower(Str::squish((string) $s)));

        return $norm($a) === $norm($b);
    }

    private function first(DOMXPath $xpath, array $queries): ?DOMElement
    {
        foreach ($queries as $query) {
            $node = $xpath->query($query)?->item(0);
            if ($node instanceof DOMElement) {
                return $node;
            }
        }

        return null;
    }

    private function text(?\DOMNode $node): ?string
    {
        $text = $node ? Str::squish(str_replace("\u{00A0}", ' ', $node->textContent)) : '';

        return $text === '' ? null : $text;
    }
}
