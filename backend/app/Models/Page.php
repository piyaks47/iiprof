<?php

namespace App\Models;

use App\Enums\PageStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Page extends Model
{
    /** Slug of the page rendered at the site root ("/"). */
    public const HOME_SLUG = 'home';

    /** URL segments owned by Laravel / the server (see deploy/htaccess/root.htaccess). */
    public const RESERVED_SLUGS = ['admin', 'api', 'build', 'storage', 'up', 'frontend', 'public', 'vendor', 'media', 'favicon-ico', 'robots-txt'];

    protected $fillable = [
        'title', 'slug', 'public_path', 'excerpt', 'content', 'featured_image', 'status',
        'show_in_menu', 'menu_order', 'meta_title', 'meta_description',
        'published_at', 'author_id',
        'banner_title', 'banner_text', 'banner_button_text', 'banner_button_url',
    ];

    protected $attributes = [
        'status' => 'draft',
        'show_in_menu' => false,
        'menu_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => PageStatus::class,
            'show_in_menu' => 'boolean',
            'menu_order' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Page $page) {
            $page->slug = static::uniqueSlug($page->slug ?: $page->title, $page->id);

            if ($page->status === PageStatus::Published && ! $page->published_at) {
                $page->published_at = now();
            }
        });

        static::deleted(function (Page $page) {
            if (\App\Support\Media\Files::owned($page->featured_image)) {
                Storage::disk('public')->delete($page->featured_image);
            }
        });
    }

    public static function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'page';

        if (in_array($base, self::RESERVED_SLUGS, true) || str_starts_with($base, 'livewire')) {
            $base .= '-page';
        }

        $slug = $base;
        $i = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn (Builder $q) => $q->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PageStatus::Published)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function scopeInMenu(Builder $query): Builder
    {
        return $query->where('show_in_menu', true)->orderBy('menu_order')->orderBy('title');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        return $query->when($term !== '', fn (Builder $q) => $q->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', "%{$term}%")->orWhere('slug', 'like', "%{$term}%");
        }));
    }

    public function isHome(): bool
    {
        return $this->slug === self::HOME_SLUG;
    }

    /** Public URL path on the React site (keeps old iiprof.com addresses like /industry/banks-nbfc). */
    public function path(): string
    {
        if ($this->isHome()) {
            return '/';
        }

        return $this->public_path ?: '/'.$this->slug;
    }

    /** Find a published page by its address on the website. */
    public static function forPath(string $path): ?self
    {
        $path = '/'.trim($path, '/');
        $slug = str_replace('/', '-', strtolower(trim($path, '/'))) ?: self::HOME_SLUG;

        return static::query()->published()
            ->where(fn (Builder $q) => $q->where('public_path', $path)->orWhere('slug', $slug))
            ->orderByRaw('CASE WHEN public_path = ? THEN 0 ELSE 1 END', [$path])
            ->first();
    }

    public function hasContent(): bool
    {
        return trim((string) $this->content) !== '';
    }

    /** Markdown content rendered to HTML (raw HTML stripped, unsafe links blocked). */
    protected function contentHtml(): Attribute
    {
        return Attribute::get(fn () => $this->content
            ? (string) Str::markdown($this->content, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ])
            : '');
    }

    protected function featuredImageUrl(): Attribute
    {
        return Attribute::get(fn () => \App\Support\Media\Files::url($this->featured_image));
    }
}
