<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Highlight;
use Illuminate\Database\Eloquent\Model;

class HighlightResource extends Resource
{
    public function key(): string { return 'highlights'; }

    public function model(): string { return Highlight::class; }

    public function label(): string { return 'Highlights'; }

    public function singular(): string { return 'highlight'; }

    public function area(): string { return 'highlights'; }

    public function icon(): string { return 'sparkles'; }

    public function subtitleFor(Model $record): ?string { return $record->getAttribute('description'); }

    public function fields(?Model $record): array
    {
        return [
            Field::text('title')->required()->max(160),
            Field::textarea('description')->rows(2)->max(500),
            Field::icon('icon')->required()->default('light-bulb'),
        ];
    }
}
