<?php

namespace App\Admin\Resources\Home;

use App\Admin\Field;
use Illuminate\Database\Eloquent\Model;

class ImpactFigureResource extends HomeItemResource
{
    public function section(): string { return 'impact'; }

    public function key(): string { return 'impact-figures'; }

    public function label(): string { return 'Impact numbers'; }

    public function singular(): string { return 'number'; }

    public function icon(): string { return 'chart'; }

    public function titleAttribute(): string { return 'value'; }

    public function titleFor(Model $record): string
    {
        return trim($record->getAttribute('value').' · '.$record->getAttribute('title'), ' ·');
    }

    public function description(): ?string { return 'Big numbers with a short explanation, shown on a dark band of the homepage.'; }

    public function fields(?Model $record): array
    {
        return [
            Field::text('value', 'Number')->required()->max(30)->hint('e.g. 2012, 15, 70+ or 24/7 — numbers count up when they scroll into view.'),
            Field::text('title', 'Label')->required()->max(120),
            Field::textarea('description')->rows(2)->max(300),
        ];
    }

    public function emptyText(): string { return 'No numbers yet — the block is hidden until you add one.'; }
}
