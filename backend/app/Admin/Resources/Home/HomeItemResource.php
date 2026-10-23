<?php

namespace App\Admin\Resources\Home;

use App\Admin\Resource;
use App\Models\HomeItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Base for the item lists of the newer homepage blocks (all stored in home_items, one `section` each). */
abstract class HomeItemResource extends Resource
{
    /** Key of the HomeSection these items belong to. */
    abstract public function section(): string;

    public function model(): string { return HomeItem::class; }

    public function area(): string { return 'home-blocks'; }

    public function query(): Builder
    {
        return HomeItem::query()->in($this->section())->ordered();
    }

    public function newRecord(): Model
    {
        return new HomeItem(['section' => $this->section()]);
    }

    public function find(int|string $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    public function subtitleFor(Model $record): ?string
    {
        return $record->getAttribute('subtitle') ?: \Illuminate\Support\Str::limit((string) $record->getAttribute('description'), 110);
    }

    public function publicUrl(Model $record): ?string
    {
        return $this->siteUrl('/#'.$this->section());
    }
}
