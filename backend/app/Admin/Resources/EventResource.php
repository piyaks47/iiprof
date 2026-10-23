<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class EventResource extends Resource
{
    public function key(): string { return 'events'; }

    public function model(): string { return Event::class; }

    public function label(): string { return 'Events'; }

    public function singular(): string { return 'event'; }

    public function area(): string { return 'events'; }

    public function icon(): string { return 'calendar'; }

    public function description(): ?string { return 'Shown in the homepage carousel; each event has its own page at /events/…'; }

    public function subtitleFor(Model $record): ?string
    {
        /** @var Event $record */
        return collect([$record->starts_on?->format('j M Y'), $record->location, $record->summary])->filter()->implode(' · ');
    }

    public function fields(?Model $record): array
    {
        return [
            Field::text('title')->required(),
            Field::text('slug', 'URL slug')->hint('Leave empty to generate from the title.')
                ->rules('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('events', 'slug')->ignore($record?->getKey())),
            Field::date('starts_on', 'Date'),
            Field::text('location'),
            Field::textarea('summary')->rows(2)->max(500)->hint('One or two sentences for the card.'),
            Field::markdown('description')->hint('Full text on the event page. Markdown supported.'),
            Field::text('button_text', 'Button text')->max(80)->hint('Optional, e.g. “Register now”.'),
            Field::link('button_url', 'Button link'),
            Field::link('media_url', 'Photos / video link')->hint('Shown on past events, e.g. /view-gallery or a YouTube link.'),
            Field::image('image')->hint('Landscape photo, at least 1200 × 800 px.'),
        ];
    }

    public function publicUrl(Model $record): ?string
    {
        /** @var Event $record */
        return $this->siteUrl($record->path());
    }
}
