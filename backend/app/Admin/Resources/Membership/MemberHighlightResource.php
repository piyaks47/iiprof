<?php

namespace App\Admin\Resources\Membership;

use App\Admin\Field;
use App\Admin\Resources\Home\HomeItemResource;
use Illuminate\Database\Eloquent\Model;

class MemberHighlightResource extends HomeItemResource
{
    public function section(): string { return 'membership_hub'; }

    public function key(): string { return 'member-highlights'; }

    public function label(): string { return 'Community highlights'; }

    public function singular(): string { return 'highlight'; }

    public function area(): string { return 'membership'; }

    public function icon(): string { return 'trending-up'; }

    public function description(): ?string { return 'Big figures in the middle panel of the membership block (“Growing member community”).'; }

    public function titleFor(Model $record): string
    {
        return trim($record->getAttribute('value').' · '.$record->getAttribute('title'), ' ·');
    }

    public function fields(?Model $record): array
    {
        return [
            Field::text('value', 'Figure')->required()->max(30)->hint('e.g. 15, ₹250, Free.'),
            Field::text('title', 'Label')->required()->max(120),
            Field::textarea('description')->rows(2)->max(300),
        ];
    }

    public function emptyText(): string { return 'No highlights yet — the middle panel is hidden.'; }
}
