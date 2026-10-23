<?php

namespace App\Admin\Resources\Membership;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\MemberOrganisation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Corporate or institutional members (one table, `kind`), each with its own screen and access area. */
abstract class OrganisationResource extends Resource
{
    abstract public function kind(): string;

    public function model(): string { return MemberOrganisation::class; }

    public function titleAttribute(): string { return 'name'; }

    public function searchable(): array { return ['name', 'category', 'city']; }

    public function query(): Builder
    {
        return MemberOrganisation::query()->kind($this->kind())->ordered();
    }

    public function newRecord(): Model
    {
        return new MemberOrganisation(['kind' => $this->kind()]);
    }

    public function find(int|string $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    public function imageFor(Model $record): ?string
    {
        /** @var MemberOrganisation $record */
        return $record->imageUrl('logo');
    }

    public function subtitleFor(Model $record): ?string
    {
        return collect([$record->getAttribute('category'), $record->getAttribute('city'), $record->getAttribute('member_since') ? 'Member since '.$record->getAttribute('member_since') : null])->filter()->implode(' · ');
    }

    public function badgesFor(Model $record): array
    {
        return array_values(array_filter([
            $record->getAttribute('grade') ? [(string) $record->getAttribute('grade'), 'sky'] : null,
            $record->getAttribute('is_featured') ? ['Featured', 'amber'] : null,
        ]));
    }

    public function fields(?Model $record): array
    {
        return [
            Field::text('name')->required()->max(255),
            Field::text('category', $this->categoryLabel())->max(120)->hint($this->categoryHint()),
            Field::text('city')->max(120),
            Field::image('logo')->hint('PNG or SVG on a transparent or white background, at least 400 px wide.'),
            Field::link('website')->hint('Optional, e.g. https://….'),
            Field::textarea('description')->rows(3)->max(800)->hint('One or two sentences, shown when the logo is pointed at.'),
            Field::text('member_since', 'Member since')->max(20)->hint('e.g. 2019.'),
            Field::text('grade', 'Membership code')->max(30)->hint($this->kind() === 'corporate' ? 'e.g. CMIIP.' : 'e.g. IIIP.'),
            Field::toggle('is_featured', 'Featured')->hint('Featured members are shown first.'),
        ];
    }

    protected function categoryLabel(): string { return 'Category'; }

    protected function categoryHint(): string { return ''; }
}
