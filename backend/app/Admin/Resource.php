<?php

namespace App\Admin;

use App\Support\Access;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Describes one admin CRUD screen (list + form), rendered by App\Livewire\Admin\Resources\{Index,Edit}.
 * Add a class in App\Admin\Resources and register it in App\Admin\Registry to get a new screen
 * with a sidebar link, permissions (via its Access area), ordering, on/off switch and image uploads.
 */
abstract class Resource
{
    /** URL key: /admin/manage/{key} */
    abstract public function key(): string;

    /** @return class-string<Model> */
    abstract public function model(): string;

    /** Plural label, e.g. "Hero slides". */
    abstract public function label(): string;

    /** Singular, lower case, e.g. "hero slide". */
    abstract public function singular(): string;

    /** Access area (App\Support\Access::AREAS) that controls who can view / manage it. */
    abstract public function area(): string;

    /** @return list<Field> */
    abstract public function fields(?Model $record): array;

    public function icon(): string
    {
        return 'document';
    }

    public function group(): string
    {
        return Access::AREAS[$this->area()]['group'] ?? 'Website';
    }

    public function description(): ?string
    {
        return null;
    }

    /** Attribute shown as the row title. */
    public function titleAttribute(): string
    {
        return 'title';
    }

    public function titleFor(Model $record): string
    {
        return (string) ($record->getAttribute($this->titleAttribute()) ?: '#'.$record->getKey());
    }

    public function subtitleFor(Model $record): ?string
    {
        return null;
    }

    /** @return list<array{0: string, 1: string}> [text, colour] */
    public function badgesFor(Model $record): array
    {
        return [];
    }

    public function imageFor(Model $record): ?string
    {
        return method_exists($record, 'imageUrl') ? $record->imageUrl() : null;
    }

    /** Rows are ordered by hand (sort_order, up/down buttons). */
    public function sortable(): bool
    {
        return true;
    }

    /** Rows have an is_active on/off switch. */
    public function toggleable(): bool
    {
        return true;
    }

    /** Show rows as a tree using parent_id (menu). */
    public function isTree(): bool
    {
        return false;
    }

    public function canCreate(): bool
    {
        return true;
    }

    public function canDelete(Model $record): bool
    {
        return true;
    }

    /** Columns searched by the search box (lists that are not ordered by hand). */
    public function searchable(): array
    {
        return [];
    }

    /** @return array<string, array{label: string, options: array<string, string>}> column => filter */
    public function filters(): array
    {
        return [];
    }

    public function query(): Builder
    {
        $query = $this->model()::query();

        return $this->sortable() ? $query->ordered() : $query->latest('id');
    }

    public function newRecord(): Model
    {
        $class = $this->model();

        return new $class;
    }

    public function find(int|string $id): Model
    {
        return $this->model()::query()->findOrFail($id);
    }

    /** Link to the record on the website, if it has its own page. */
    public function publicUrl(Model $record): ?string
    {
        return null;
    }

    /** Extra checks / changes before saving. Throw a ValidationException (keys data.*) to reject. */
    public function beforeSave(Model $record, array $data): void {}

    /** Number shown next to the sidebar link (e.g. unread enquiries). */
    public function badge(): ?int
    {
        return null;
    }

    public function emptyText(): string
    {
        return 'Nothing here yet.';
    }

    /** Extra panel on the edit screen: [title, text, url, link label]. */
    public function aside(Model $record): ?array
    {
        return null;
    }

    public function url(): string
    {
        return route('admin.resources.index', $this->key());
    }

    protected function siteUrl(string $path): string
    {
        return rtrim((string) config('app.url'), '/').$path;
    }
}
