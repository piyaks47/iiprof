<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Registry;
use App\Admin\Resource;
use App\Models\HomeSection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HomeSectionResource extends Resource
{
    public function key(): string { return 'home-sections'; }

    public function model(): string { return HomeSection::class; }

    public function label(): string { return 'Homepage sections'; }

    public function singular(): string { return 'section'; }

    public function area(): string { return 'home-sections'; }

    public function icon(): string { return 'home'; }

    public function description(): ?string
    {
        return 'The blocks of the homepage, top to bottom. Reorder them, switch them off, or edit their headings. Each block’s items (slides, events, photos …) have their own screen.';
    }

    public function titleAttribute(): string { return 'name'; }

    public function subtitleFor(Model $record): ?string
    {
        /** @var HomeSection $record */
        return $record->definition()['description'];
    }

    public function badgesFor(Model $record): array
    {
        /** @var HomeSection $record */
        $items = $record->definition()['items'];
        if (! $items || ! ($resource = Registry::find($items))) {
            return [];
        }

        $count = $resource->query()->where('is_active', true)->count();

        return [[$count.' '.($count === 1 ? $resource->singular() : \Illuminate\Support\Str::plural($resource->singular())), $count ? 'sky' : 'amber']];
    }

    public function imageFor(Model $record): ?string { return null; }

    public function canCreate(): bool { return false; }

    public function canDelete(Model $record): bool { return false; }

    public function query(): Builder
    {
        HomeSection::ensureAll();

        return parent::query()->whereIn('key', array_keys(HomeSection::SECTIONS));
    }

    public function fields(?Model $record): array
    {
        /** @var HomeSection|null $record */
        $all = [
            'eyebrow' => Field::text('eyebrow', 'Small heading')->max(120)->hint('Short line in gold above the heading, e.g. “Who we are”.'),
            'title' => Field::text('title', 'Heading'),
            'title_accent' => Field::text('title_accent', 'Heading — highlighted part')->hint('Shown after the heading in the accent colour, e.g. heading “About” + highlight “Us”.'),
            'subtitle' => Field::textarea('subtitle', $record?->key === 'contact' ? 'Quote author' : 'Intro text')->rows(2)->max(500),
            'body' => Field::markdown('body', $record?->key === 'contact' ? 'Quote' : 'Text')->rows(8)->hint('Markdown: **bold**, *italic*, - lists, [link](https://…).'),
            'button_text' => Field::text('button_text', $record?->key === 'hero' ? 'Second button text' : 'Button text')->max(80),
            'button_url' => Field::link('button_url', 'Button link')->hint('A page path like /about, or a full URL.'),
            'image' => Field::image('image', in_array($record?->key, ['highlights', 'contact', 'impact', 'join'], true) ? 'Background image' : 'Image')->hint('JPG, PNG or WebP, up to 5 MB.'),
        ];

        return array_values(array_filter($all, fn (Field $field) => $record?->uses($field->name)));
    }

    public function aside(Model $record): ?array
    {
        /** @var HomeSection $record */
        $definition = $record->definition();
        $items = $definition['items'] ? Registry::find($definition['items']) : null;

        return [
            'title' => $definition['name'],
            'text' => $definition['description'],
            'url' => $items?->url(),
            'link' => $items ? 'Manage '.strtolower($items->label()) : null,
        ];
    }

    public function publicUrl(Model $record): ?string
    {
        return $this->siteUrl('/#'.$record->getAttribute('key'));
    }
}
