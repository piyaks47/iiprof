<?php

namespace App\Admin\Resources\Home;

use App\Admin\Field;
use Illuminate\Database\Eloquent\Model;

class FeatureTileResource extends HomeItemResource
{
    public function section(): string { return 'pillars'; }

    public function key(): string { return 'feature-tiles'; }

    public function label(): string { return 'Feature tiles'; }

    public function singular(): string { return 'tile'; }

    public function icon(): string { return 'dashboard'; }

    public function description(): ?string { return 'Large photo tiles on the homepage, each linking to one area of IIP (Hunar, Jeevika, Khazana …).'; }

    public function fields(?Model $record): array
    {
        return [
            Field::text('title')->required()->max(120),
            Field::text('subtitle', 'Tag')->max(120)->hint('Short line above the title, e.g. “Education & skills”.'),
            Field::textarea('description')->rows(3)->max(400),
            Field::icon('icon')->default('sparkles'),
            Field::image('image', 'Photo')->hint('Landscape photo, at least 1200 × 800 px.'),
            Field::text('button_text', 'Link text')->max(80)->hint('Optional, e.g. “Explore Hunar”.'),
            Field::link('button_url', 'Link'),
        ];
    }

    public function emptyText(): string { return 'No tiles yet — the block is hidden until you add one.'; }
}
