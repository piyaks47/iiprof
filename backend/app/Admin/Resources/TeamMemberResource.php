<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TeamMemberResource extends Resource
{
    public function key(): string { return 'team'; }

    public function model(): string { return TeamMember::class; }

    public function label(): string { return 'Management & team'; }

    public function singular(): string { return 'person'; }

    public function area(): string { return 'team'; }

    public function icon(): string { return 'user-group'; }

    public function description(): ?string
    {
        return 'People shown on the management pages (/management/director-council, /management/governing-body …), in this order.';
    }

    public function titleAttribute(): string { return 'name'; }

    public function subtitleFor(Model $record): ?string { return $record->getAttribute('role'); }

    public function imageFor(Model $record): ?string
    {
        /** @var TeamMember $record */
        return $record->imageUrl('photo');
    }

    public function badgesFor(Model $record): array
    {
        return [[TeamMember::GROUPS[$record->getAttribute('group')] ?? $record->getAttribute('group'), 'brand']];
    }

    public function query(): Builder
    {
        $order = array_keys(TeamMember::GROUPS);
        $query = TeamMember::query();
        // "group" is a reserved word: let the grammar quote it for MySQL / SQLite.
        $column = $query->getQuery()->getGrammar()->wrap('group');
        $case = 'CASE '.collect($order)->map(fn ($g, $i) => "WHEN {$column} = ? THEN {$i}")->implode(' ').' ELSE 99 END';

        return $query->orderByRaw($case, $order)->ordered();
    }

    public function filters(): array
    {
        return ['group' => ['label' => 'All groups', 'options' => TeamMember::GROUPS]];
    }

    public function fields(?Model $record): array
    {
        return [
            Field::select('group', 'Page')->required()->options(TeamMember::GROUPS)->default('director-council'),
            Field::text('name')->required()->max(160),
            Field::text('role', 'Designation')->max(255)->hint('e.g. Professor & Head, ACED'),
            Field::textarea('bio', 'Short profile')->rows(3)->max(2000),
            Field::image('photo')->hint('Portrait, square or 4:5, at least 600 px wide.'),
        ];
    }

    public function publicUrl(Model $record): ?string
    {
        return $this->siteUrl('/management/'.$record->getAttribute('group'));
    }
}
