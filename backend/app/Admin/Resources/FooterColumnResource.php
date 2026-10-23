<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\FooterColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FooterColumnResource extends Resource
{
    public function key(): string { return 'footer-columns'; }

    public function model(): string { return FooterColumn::class; }

    public function label(): string { return 'Footer columns'; }

    public function singular(): string { return 'footer column'; }

    public function area(): string { return 'footer'; }

    public function icon(): string { return 'queue'; }

    public function query(): Builder
    {
        return parent::query()->withCount('links');
    }

    public function badgesFor(Model $record): array
    {
        return [[$record->getAttribute('links_count').' links', 'slate']];
    }

    public function fields(?Model $record): array
    {
        return [Field::text('title')->required()->max(120)];
    }

    public function aside(Model $record): ?array
    {
        return [
            'title' => 'Links',
            'text' => 'Links of this column are managed in Footer links.',
            'url' => route('admin.resources.index', ['resource' => 'footer-links', 'filter' => ['footer_column_id' => $record->getKey()]]),
            'link' => 'Manage links',
        ];
    }
}
