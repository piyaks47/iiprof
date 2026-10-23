<?php

namespace App\Admin\Resources\News;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Seminar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class SeminarResource extends Resource
{
    public function key(): string { return 'seminars'; }

    public function model(): string { return Seminar::class; }

    public function label(): string { return 'Seminars'; }

    public function singular(): string { return 'seminar'; }

    public function area(): string { return 'seminars'; }

    public function icon(): string { return 'slides'; }

    public function description(): ?string { return 'Seminar banners on the homepage (image, title, button to the destination link) and a page each at /seminars/… with the full description.'; }

    public function subtitleFor(Model $record): ?string
    {
        /** @var Seminar $record */
        return collect([$record->starts_on?->format('j M Y'), $record->location, $record->link])->filter()->implode(' · ');
    }

    public function fields(?Model $record): array
    {
        return [
            Field::text('title')->required(),
            Field::text('slug', 'URL slug')->hint('Leave empty to generate from the title.')
                ->rules('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('seminars', 'slug')->ignore($record?->getKey())),
            Field::image('image', 'Featured image')->required()->hint('Wide banner photo, at least 1600 × 800 px.'),
            Field::link('link', 'Destination link')->hint('Where the banner button goes: a page (/events), a form (#enquiry) or a website. Empty = the seminar page.'),
            Field::text('button_text', 'Button text')->max(80)->default('Know more'),
            Field::date('starts_on', 'Date')->hint('Optional.'),
            Field::text('location')->max(160)->hint('Optional, e.g. Kolkata or Online.'),
            Field::textarea('summary')->rows(2)->max(600)->hint('One or two sentences on the banner. Empty = the start of the description.'),
            Field::markdown('description', 'Description')->rows(12)->hint('Rich text: use the buttons for bold, headings, lists and links.'),
        ];
    }

    public function publicUrl(Model $record): ?string
    {
        /** @var Seminar $record */
        return $this->siteUrl($record->path());
    }

    public function emptyText(): string { return 'No seminars yet — the seminar banners are hidden until you add one.'; }
}
