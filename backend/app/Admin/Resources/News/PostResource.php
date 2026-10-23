<?php

namespace App\Admin\Resources\News;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class PostResource extends Resource
{
    public function key(): string { return 'news'; }

    public function model(): string { return Post::class; }

    public function label(): string { return 'News & blogs'; }

    public function singular(): string { return 'post'; }

    public function area(): string { return 'news'; }

    public function icon(): string { return 'megaphone'; }

    public function sortable(): bool { return false; }

    public function searchable(): array { return ['title', 'excerpt', 'author']; }

    public function description(): ?string { return 'News, blog posts and articles: the gallery on the homepage, the list at /news and a page each at /news/…. Posts with a future date appear on that day.'; }

    public function query(): Builder
    {
        return Post::query()->orderByDesc('published_on')->orderByDesc('id');
    }

    public function subtitleFor(Model $record): ?string
    {
        /** @var Post $record */
        return collect([$record->published_on?->format('j M Y'), $record->author, $record->excerpt])->filter()->implode(' · ');
    }

    public function badgesFor(Model $record): array
    {
        /** @var Post $record */
        $badges = [[Post::TYPES[$record->type] ?? $record->type, 'sky']];
        if ($record->is_featured) {
            $badges[] = ['Featured', 'amber'];
        }
        if ($record->published_on?->isFuture()) {
            $badges[] = ['Scheduled', 'slate'];
        }

        return $badges;
    }

    public function filters(): array
    {
        return ['type' => ['label' => 'Type', 'options' => Post::TYPES]];
    }

    public function fields(?Model $record): array
    {
        return [
            Field::select('type')->options(Post::TYPES)->required()->default('news'),
            Field::text('title')->required(),
            Field::text('slug', 'URL slug')->hint('Leave empty to generate from the title.')
                ->rules('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('posts', 'slug')->ignore($record?->getKey())),
            Field::date('published_on', 'Date')->hint('Empty = shown right away; a future date publishes it on that day.'),
            Field::text('author')->max(160),
            Field::textarea('excerpt')->rows(3)->max(600)->hint('Shown on the cards. Leave empty to use the start of the text.'),
            Field::markdown('body', 'Text')->rows(14)->hint('Use the buttons for bold, headings, lists and links.'),
            Field::image('image', 'Featured image')->hint('Landscape photo, at least 1200 × 800 px.'),
            Field::link('external_url', 'Read on another website')->hint('Optional: the card links there instead of the post page.'),
            Field::toggle('is_featured', 'Featured')->hint('Shown large at the start of the homepage gallery.'),
        ];
    }

    public function publicUrl(Model $record): ?string
    {
        /** @var Post $record */
        return $this->siteUrl($record->path());
    }

    public function emptyText(): string { return 'No posts yet — the news block is hidden until you add one.'; }
}
