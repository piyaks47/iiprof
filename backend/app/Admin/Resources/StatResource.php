<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Stat;
use Illuminate\Database\Eloquent\Model;

class StatResource extends Resource
{
    public function key(): string { return 'stats'; }

    public function model(): string { return Stat::class; }

    public function label(): string { return 'Key figures'; }

    public function singular(): string { return 'figure'; }

    public function area(): string { return 'stats'; }

    public function icon(): string { return 'chart'; }

    public function description(): ?string { return 'Numbers that count up on the homepage.'; }

    public function titleFor(Model $record): string
    {
        return $record->getAttribute('value').' — '.$record->getAttribute('label');
    }

    public function fields(?Model $record): array
    {
        return [
            Field::text('value', 'Number')->required()->max(20)->hint('e.g. 18, 250+, 24/7, 98%. Digits count up; the rest is kept.'),
            Field::text('label', 'Label')->required()->max(120),
        ];
    }
}
