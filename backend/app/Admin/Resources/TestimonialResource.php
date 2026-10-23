<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TestimonialResource extends Resource
{
    public function key(): string { return 'testimonials'; }

    public function model(): string { return Testimonial::class; }

    public function label(): string { return 'Testimonials'; }

    public function singular(): string { return 'testimonial'; }

    public function area(): string { return 'testimonials'; }

    public function icon(): string { return 'chat'; }

    public function description(): ?string { return 'Shown on the /testimonials page.'; }

    public function titleAttribute(): string { return 'name'; }

    public function subtitleFor(Model $record): ?string
    {
        return collect([$record->getAttribute('role'), Str::limit((string) $record->getAttribute('quote'), 80)])->filter()->implode(' · ') ?: null;
    }

    public function imageFor(Model $record): ?string
    {
        /** @var Testimonial $record */
        return $record->imageUrl('photo');
    }

    public function badgesFor(Model $record): array
    {
        return array_values(array_filter([
            $record->getAttribute('video_url') ? ['Video', 'sky'] : null,
            $record->getAttribute('quote') ? null : ['No text yet', 'amber'],
        ]));
    }

    public function fields(?Model $record): array
    {
        return [
            Field::text('name')->required()->max(160),
            Field::text('role', 'Designation / organisation')->max(255),
            Field::textarea('quote', 'Testimonial')->rows(4)->max(3000)->hint('The person’s own words.'),
            Field::link('video_url', 'Video link')->hint('YouTube link or a video file (.mp4) — shown as a play button.'),
            Field::image('photo')->hint('Square portrait.'),
        ];
    }
}
