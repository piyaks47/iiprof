<?php

namespace App\Admin\Resources\Training;

use App\Admin\Field;
use App\Admin\Resources\Home\HomeItemResource;
use Illuminate\Database\Eloquent\Model;

class TrainingFormatResource extends HomeItemResource
{
    public function section(): string { return 'training_formats'; }

    public function key(): string { return 'training-formats'; }

    public function label(): string { return 'Types of training'; }

    public function singular(): string { return 'type'; }

    public function area(): string { return 'training'; }

    public function icon(): string { return 'puzzle'; }

    public function description(): ?string { return 'How training is delivered (open batches, in-company, online), shown as cards with a link.'; }

    public function fields(?Model $record): array
    {
        return [
            Field::text('title')->required()->max(120),
            Field::text('subtitle', 'Tag')->max(120)->hint('Short line, e.g. “At IIP centres”.'),
            Field::textarea('description')->rows(3)->max(500),
            Field::icon('icon')->default('academic-cap'),
            Field::text('button_text', 'Link text')->max(80),
            Field::link('button_url', 'Link'),
        ];
    }

    public function emptyText(): string { return 'Nothing here yet — the block is hidden until you add a type.'; }
}
