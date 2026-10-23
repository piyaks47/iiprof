<?php

namespace App\Models;

use App\Models\Concerns\HasImages;
use App\Models\Concerns\Orderable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Website navigation, up to three levels deep. Links to a page or to any URL. */
class MenuItem extends Model
{
    use HasImages, Orderable;

    public const MAX_DEPTH = 3;

    protected string $sortGroup = 'parent_id';

    protected $fillable = ['parent_id', 'label', 'url', 'page_id', 'image', 'new_tab', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['new_tab' => 'boolean'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->ordered();
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function link(): string
    {
        if ($this->page) {
            return $this->page->path();
        }

        return $this->url ?: '';
    }

    /** 1 for a top-level item. */
    public function depth(): int
    {
        $depth = 1;
        for ($item = $this->parent; $item && $depth < 10; $item = $item->parent) {
            $depth++;
        }

        return $depth;
    }

    /** Ids of this item and everything below it. */
    public function subtreeIds(): array
    {
        $ids = [$this->id];
        $frontier = [$this->id];

        while ($frontier) {
            $frontier = static::query()->whereIn('parent_id', $frontier)->pluck('id')->all();
            $ids = array_merge($ids, $frontier);
        }

        return $ids;
    }

    /**
     * All items as a flat list in tree order (parents before children), each with a `depth`.
     *
     * @return Collection<int, MenuItem>
     */
    public static function flatTree(bool $activeOnly = false): Collection
    {
        $all = static::query()->with('page:id,slug,public_path,title,status')->ordered()
            ->when($activeOnly, fn ($q) => $q->active())->get();
        $byParent = $all->groupBy(fn (MenuItem $item) => (int) $item->parent_id);
        $out = new Collection;

        $walk = function (int $parentId, int $depth) use (&$walk, $byParent, $out) {
            foreach ($byParent->get($parentId, []) as $item) {
                $item->setAttribute('depth', $depth);
                $out->push($item);
                $walk($item->id, $depth + 1);
            }
        };
        $walk(0, 1);

        return $out;
    }

    /** Nested array for the public API. */
    public static function tree(): array
    {
        $items = static::query()->with('page:id,slug,public_path,title,status,published_at')->active()->ordered()->get();
        $byParent = $items->groupBy(fn (MenuItem $item) => (int) $item->parent_id);

        $build = function (int $parentId, int $depth) use (&$build, $byParent): array {
            if ($depth > self::MAX_DEPTH) {
                return [];
            }

            return $byParent->get($parentId, collect())
                ->reject(fn (MenuItem $item) => $item->page_id && ! $item->page) // page deleted
                ->map(fn (MenuItem $item) => [
                    'id' => $item->id,
                    'label' => $item->label,
                    'url' => $item->link() ?: null,
                    'new_tab' => $item->new_tab,
                    'image_url' => $item->imageUrl(),
                    'children' => $build($item->id, $depth + 1),
                ])->values()->all();
        };

        return $build(0, 1);
    }

    /** @var array<string, ?string>|null */
    private static ?array $bannerMap = null;

    /**
     * Image for the banner of the page at $path: the image of the menu item linking to it, or of the
     * nearest item above it that has one (Khazana → Lender → Banks/NBFC uses Lender's). Null if none.
     */
    public static function imageForPath(string $path): ?string
    {
        if (self::$bannerMap === null) {
            self::$bannerMap = [];
            $inherited = [];
            try {
                $items = static::flatTree(activeOnly: true);
            } catch (\Throwable) {
                $items = collect(); // menu table not migrated yet
            }
            foreach ($items as $item) {
                $inherited = array_slice($inherited, 0, $item->depth - 1);
                $inherited[] = $item->image ?: ($inherited[$item->depth - 2] ?? null);
                $link = $item->link();
                if ($link !== '' && str_starts_with($link, '/') && ! isset(self::$bannerMap[$link])) {
                    self::$bannerMap[rtrim($link, '/') ?: '/'] = end($inherited) ?: null;
                }
            }
        }

        $image = self::$bannerMap[rtrim('/'.ltrim($path, '/'), '/') ?: '/'] ?? null;

        return \App\Support\Media\Files::url($image);
    }

    public static function flushBannerMap(): void
    {
        self::$bannerMap = null;
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::flushBannerMap());
        static::deleted(fn () => self::flushBannerMap());
    }
}
