<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\FooterColumn;
use App\Models\FooterLink;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FooterLinkResource extends Resource
{
    public function key(): string { return 'footer-links'; }

    public function model(): string { return FooterLink::class; }

    public function label(): string { return 'Footer links'; }

    public function singular(): string { return 'footer link'; }

    public function area(): string { return 'footer'; }

    public function icon(): string { return 'link'; }

    public function titleAttribute(): string { return 'label'; }

    public function subtitleFor(Model $record): ?string { return $record->getAttribute('url'); }

    public function badgesFor(Model $record): array
    {
        /** @var FooterLink $record */
        return array_values(array_filter([
            [$record->column?->title ?? '—', 'brand'],
            $record->new_tab ? ['New tab', 'slate'] : null,
        ]));
    }

    public function query(): Builder
    {
        return FooterLink::query()->with('column')
            ->leftJoin('footer_columns', 'footer_columns.id', '=', 'footer_links.footer_column_id')
            ->select('footer_links.*')
            ->orderBy('footer_columns.sort_order')
            ->orderBy('footer_links.footer_column_id')
            ->orderBy('footer_links.sort_order')
            ->orderBy('footer_links.id');
    }

    public function filters(): array
    {
        return ['footer_column_id' => [
            'label' => 'All columns',
            'options' => FooterColumn::query()->ordered()->pluck('title', 'id')->mapWithKeys(fn ($t, $id) => [(string) $id => $t])->all(),
        ]];
    }

    public function fields(?Model $record): array
    {
        return [
            Field::select('footer_column_id', 'Column')->required()
                ->options(fn () => FooterColumn::query()->ordered()->pluck('title', 'id')->all(), 'Choose a column…'),
            Field::text('label')->required()->max(120),
            Field::link('url', 'Link')->required(),
            Field::toggle('new_tab', 'Open in a new tab'),
        ];
    }
}
