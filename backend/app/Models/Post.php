<?php

namespace App\Models;

use App\Models\Concerns\HasImages;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** A news item, blog post or article (/news and /news/<slug>). */
class Post extends Model
{
    use HasImages, HasSlug;

    public const TYPES = ['news' => 'News', 'blog' => 'Blog', 'article' => 'Article'];

    protected $fillable = ['type', 'title', 'slug', 'excerpt', 'body', 'image', 'author', 'published_on', 'external_url', 'is_featured', 'is_active'];

    protected $attributes = ['type' => 'news', 'is_active' => true];

    protected function casts(): array
    {
        return ['published_on' => 'date', 'is_featured' => 'boolean', 'is_active' => 'boolean'];
    }

    /** Active and published (no date, or a date that has come). Newest first. */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('published_on')->orWhereDate('published_on', '<=', now()->toDateString()))
            ->orderByDesc('published_on')->orderByDesc('id');
    }

    public function path(): string
    {
        return '/news/'.$this->slug;
    }

    /** Minutes to read (about 200 words a minute). */
    public function readingMinutes(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags((string) $this->body)) / 200));
    }

    public function toSummary(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => self::TYPES[$this->type] ?? Str::headline((string) $this->type),
            'title' => $this->title,
            'slug' => $this->slug,
            'path' => $this->path(),
            'excerpt' => $this->excerpt ?: Str::limit(trim(strip_tags(Str::markdown((string) $this->body))), 180),
            'image_url' => $this->imageUrl(),
            'author' => $this->author,
            'published_on' => $this->published_on?->toDateString(),
            'reading_minutes' => $this->readingMinutes(),
            'external_url' => $this->external_url,
            'is_featured' => $this->is_featured,
        ];
    }

    public function toDetail(): array
    {
        return $this->toSummary() + [
            'body' => $this->body,
            'body_html' => $this->body ? (string) Str::markdown($this->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]) : '',
        ];
    }
}
