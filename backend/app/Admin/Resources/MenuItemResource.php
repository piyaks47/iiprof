<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class MenuItemResource extends Resource
{
    public function key(): string { return 'menu'; }

    public function model(): string { return MenuItem::class; }

    public function label(): string { return 'Menu'; }

    public function singular(): string { return 'menu item'; }

    public function area(): string { return 'menu'; }

    public function icon(): string { return 'menu'; }

    public function description(): ?string
    {
        return 'Main navigation of the website, up to three levels. An item without a link only opens its drop-down. Each item can have a featured image; items without one use their parent’s.';
    }

    public function titleAttribute(): string { return 'label'; }

    public function isTree(): bool { return true; }

    public function subtitleFor(Model $record): ?string
    {
        /** @var MenuItem $record */
        if ($record->page_id) {
            return 'Page: '.($record->page?->title ?? '(deleted)').' · '.$record->link();
        }

        return $record->url ?: 'Drop-down only';
    }

    public function badgesFor(Model $record): array
    {
        return $record->getAttribute('new_tab') ? [['New tab', 'slate']] : [];
    }

    public function query(): Builder
    {
        return MenuItem::query()->with('page:id,title,slug')->ordered();
    }

    public function fields(?Model $record): array
    {
        return [
            Field::text('label')->required()->max(120),
            Field::select('parent_id', 'Parent')->options(fn (?Model $item) => $this->parentOptions($item), '— Top level —')
                ->hint('Put the item inside another item’s drop-down.'),
            Field::select('page_id', 'Page')->options(fn () => Page::query()->orderBy('title')->pluck('title', 'id')->all(), '— No page —')
                ->hint('Link to one of your pages, or enter a URL below.'),
            Field::link('url', 'Or a URL')->hint('Used when no page is chosen, e.g. /contact or https://….'),
            Field::toggle('new_tab', 'Open in a new tab'),
            Field::image('image', 'Featured image')->hint('Shown in the drop-down menu, and as the banner of pages under this item that have no banner image of their own. Landscape, at least 1440 × 720 px.'),
        ];
    }

    /** @return array<int, string> */
    private function parentOptions(?Model $item): array
    {
        /** @var MenuItem|null $item */
        $exclude = $item?->exists ? $item->subtreeIds() : [];

        return MenuItem::flatTree()
            ->filter(fn (MenuItem $m) => $m->depth < MenuItem::MAX_DEPTH && ! in_array($m->id, $exclude, true))
            ->mapWithKeys(fn (MenuItem $m) => [$m->id => str_repeat('— ', $m->depth - 1).$m->label])
            ->all();
    }

    public function beforeSave(Model $record, array $data): void
    {
        /** @var MenuItem $record */
        if ($record->parent_id) {
            $parent = MenuItem::query()->find($record->parent_id);
            $height = $record->exists ? $this->subtreeHeight($record) : 1;

            if (! $parent || $parent->depth() + $height > MenuItem::MAX_DEPTH) {
                throw ValidationException::withMessages(['data.parent_id' => 'Menus can be at most '.MenuItem::MAX_DEPTH.' levels deep.']);
            }
        }

        if ($record->page_id) {
            $record->url = null;
        }

        // Moving to another parent: put it at the end of its new siblings.
        if ($record->exists && $record->isDirty('parent_id')) {
            $record->sort_order = (int) $record->siblingsQuery()->whereKeyNot($record->id)->max('sort_order') + 1;
        }
    }

    private function subtreeHeight(MenuItem $item, int $guard = 0): int
    {
        $children = MenuItem::query()->where('parent_id', $item->id)->get();

        return 1 + ($children->isEmpty() || $guard > 5 ? 0 : $children->max(fn (MenuItem $c) => $this->subtreeHeight($c, $guard + 1)));
    }

    public function emptyText(): string
    {
        return 'No menu items yet — the website shows the pages marked “In menu” instead.';
    }
}
