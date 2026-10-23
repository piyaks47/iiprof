<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\HeroSlide;
use Illuminate\Database\Eloquent\Model;

class HeroSlideResource extends Resource
{
    public function key(): string { return 'hero-slides'; }

    public function model(): string { return HeroSlide::class; }

    public function label(): string { return 'Hero slides'; }

    public function singular(): string { return 'slide'; }

    public function area(): string { return 'hero-slides'; }

    public function icon(): string { return 'slides'; }

    public function description(): ?string { return 'Slides of the homepage slider, in the order they play.'; }

    public function subtitleFor(Model $record): ?string { return $record->getAttribute('subtitle'); }

    public function fields(?Model $record): array
    {
        return [
            Field::text('eyebrow', 'Small heading')->max(120)->hint('e.g. “Brain Bank of Global Talents”.'),
            Field::text('title', 'Heading')->required(),
            Field::textarea('subtitle', 'Text')->rows(2)->max(500),
            Field::text('button_text', 'Button text')->max(80),
            Field::link('button_url', 'Button link'),
            Field::image('image', 'Background image')->hint('Wide photo, at least 1920 × 1000 px. Without an image an elegant pattern is shown.'),
        ];
    }

    public function emptyText(): string { return 'No slides yet — the slider is hidden until you add one.'; }
}
