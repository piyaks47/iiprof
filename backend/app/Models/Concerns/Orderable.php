<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * `sort_order` + `is_active` columns: scopes and "append to the end" on create.
 * Models whose order is per group (menu children, links of a footer column) set $sortGroup.
 */
trait Orderable
{
    public static function bootOrderable(): void
    {
        static::creating(function ($model) {
            if (! $model->sort_order) {
                $model->sort_order = (int) $model->siblingsQuery()->max('sort_order') + 1;
            }
        });
    }

    public function initializeOrderable(): void
    {
        $this->mergeCasts(['sort_order' => 'integer', 'is_active' => 'boolean']);
    }

    /** Column that groups siblings for ordering (null = whole table). */
    public function sortGroupColumn(): ?string
    {
        return property_exists($this, 'sortGroup') ? $this->sortGroup : null;
    }

    /** Rows this one is ordered among. */
    public function siblingsQuery(): Builder
    {
        $query = static::query();

        if ($column = $this->sortGroupColumn()) {
            $value = $this->getAttribute($column);
            $value === null ? $query->whereNull($column) : $query->where($column, $value);
        }

        return $query;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('is_active'), true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy($this->qualifyColumn('sort_order'))->orderBy($this->qualifyColumn('id'));
    }
}
