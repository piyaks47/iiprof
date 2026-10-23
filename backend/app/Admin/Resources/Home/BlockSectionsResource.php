<?php

namespace App\Admin\Resources\Home;

use App\Admin\Resources\HomeSectionResource;
use App\Models\HomeSection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Headings, texts and visibility of the homepage blocks of one access area (membership, training),
 * for people who manage that area. Their place on the page is set under Homepage → Homepage sections.
 */
abstract class BlockSectionsResource extends HomeSectionResource
{
    public function sortable(): bool { return false; }

    public function icon(): string { return 'home'; }

    public function singular(): string { return 'section'; }

    public function query(): Builder
    {
        HomeSection::ensureAll();

        return HomeSection::query()->whereIn('key', HomeSection::keysIn($this->area()))->ordered();
    }

    public function find(int|string $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    public function description(): ?string
    {
        return 'Headings, texts and visibility of these homepage blocks. Their place on the page is set under Homepage → Homepage sections.';
    }
}
