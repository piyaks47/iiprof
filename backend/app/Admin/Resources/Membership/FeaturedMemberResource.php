<?php

namespace App\Admin\Resources\Membership;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\FeaturedMember;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FeaturedMemberResource extends Resource
{
    public function key(): string { return 'member-directory'; }

    public function model(): string { return FeaturedMember::class; }

    public function label(): string { return 'Member directory'; }

    public function singular(): string { return 'member'; }

    public function area(): string { return 'membership'; }

    public function icon(): string { return 'user-group'; }

    public function titleAttribute(): string { return 'name'; }

    public function description(): ?string { return 'Members shown in the membership block, in tabs by category (only categories with members get a tab). Up / down orders them within their category.'; }

    public function query(): Builder
    {
        return FeaturedMember::query()->orderBy('category')->ordered();
    }

    public function subtitleFor(Model $record): ?string
    {
        return collect([$record->getAttribute('designation'), $record->getAttribute('organisation'), $record->getAttribute('city')])->filter()->implode(' · ');
    }

    public function badgesFor(Model $record): array
    {
        return [[FeaturedMember::CATEGORIES[$record->getAttribute('category')] ?? (string) $record->getAttribute('category'), 'sky']];
    }

    public function imageFor(Model $record): ?string
    {
        /** @var FeaturedMember $record */
        return $record->imageUrl('photo');
    }

    public function filters(): array
    {
        return ['category' => ['label' => 'Category', 'options' => FeaturedMember::CATEGORIES]];
    }

    public function fields(?Model $record): array
    {
        return [
            Field::select('category')->options(FeaturedMember::CATEGORIES)->required()->default('individual'),
            Field::text('name')->required()->max(160),
            Field::text('designation')->max(255)->hint('e.g. Managing Director.'),
            Field::text('organisation')->max(255),
            Field::text('city')->max(120),
            Field::image('photo', 'Photo or logo')->hint('Square works best, at least 400 × 400 px.'),
            Field::link('url', 'Profile link')->hint('Optional: a page or website about the member.'),
        ];
    }

    public function emptyText(): string { return 'No members yet — the directory panel is hidden.'; }
}
