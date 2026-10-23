<?php

namespace App\Support;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\Setting;
use App\Support\Import\MarkdownTidy;
use App\Support\Import\PageImporter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The original page texts of iiprof.com, shipped with the code (database/seeders/data/iiprof-pages.json,
 * copied from the live site and tidied up by App\Support\Import\MarkdownTidy), plus the biographies of
 * the Director Council and the testimonial quotes.
 *
 * applyOnce() puts them into pages whose text is still empty or the text this CMS shipped with —
 * pages edited in the admin are never touched. Images in the texts point at iiprof.com until
 * `php artisan iiprof:localize-images` copies them (the deploy runs it right after).
 */
class IiprofPages
{
    public const FILE = 'database/seeders/data/iiprof-pages.json';

    public const MARKER = 'iiprof_pages';

    private static ?array $data = null;

    /** @return array{pages: array<string, array{title: ?string, source: string, content: string}>, team: array<string, list<array>>, testimonials: list<array>} */
    public static function data(): array
    {
        if (self::$data === null) {
            $file = base_path(self::FILE);
            $json = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
            self::$data = [
                'pages' => $json['pages'] ?? [],
                'team' => $json['team'] ?? [],
                'testimonials' => $json['testimonials'] ?? [],
            ];
        }

        return self::$data;
    }

    /** The iiprof.com text for an address of this site (/about-us, /industry/banks-nbfc …). */
    public static function content(string $path): ?string
    {
        return self::data()['pages']['/'.trim($path, '/')]['content'] ?? null;
    }

    public static function applied(): bool
    {
        return filled(Setting::query()->where('key', self::MARKER)->value('value'));
    }

    /** @return array<string, string> what happened, per address */
    public static function applyOnce(): array
    {
        if (self::applied() || self::data()['pages'] === []) {
            return [];
        }

        $report = self::apply();
        Setting::query()->updateOrCreate(['key' => self::MARKER], ['value' => now()->toIso8601String()]);

        return $report;
    }

    /** @return array<string, string> */
    public static function apply(bool $force = false): array
    {
        $importer = new PageImporter;
        $report = [];

        DB::transaction(function () use ($importer, $force, &$report) {
            foreach (self::data()['pages'] as $path => $source) {
                $page = self::page($path) ?? Page::query()->create([
                    'title' => MarkdownTidy::headingCase((string) ($source['title'] ?: Str::headline(basename($path)))),
                    'slug' => str_replace('/', '-', trim($path, '/')),
                    'public_path' => str_contains(trim($path, '/'), '/') ? $path : null,
                    'status' => PageStatus::Published,
                ]);
                $report[$path] = $importer->apply($page, ['content' => $source['content'], 'team' => [], 'testimonials' => []], $force);
            }

            foreach (self::data()['team'] as $group => $people) {
                if ($page = self::page('/management/'.$group)) {
                    $report['/management/'.$group] = $importer->apply($page, ['content' => '', 'team' => $people, 'testimonials' => []], $force);
                }
            }

            if (($items = self::data()['testimonials']) && ($page = self::page('/testimonials'))) {
                $report['/testimonials'] = $importer->apply($page, ['content' => '', 'team' => [], 'testimonials' => $items], $force);
            }
        });

        return $report;
    }

    /** Every iiprof.com file the texts use (for MediaLocalizer's repairs). */
    public static function json(): string
    {
        return (string) json_encode(self::data(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private static function page(string $path): ?Page
    {
        return Page::query()->where('public_path', $path)->first()
            ?? Page::query()->where('slug', str_replace('/', '-', strtolower(trim($path, '/'))))->first();
    }
}
