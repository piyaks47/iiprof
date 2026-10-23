<?php

namespace App\Admin\Resources\Membership;

use App\Admin\Field;
use App\Admin\Resources\Home\HomeItemResource;
use Illuminate\Database\Eloquent\Model;

class MembershipObjectiveResource extends HomeItemResource
{
    public function section(): string { return 'membership_objectives'; }

    public function key(): string { return 'membership-objectives'; }

    public function label(): string { return 'Membership objectives'; }

    public function singular(): string { return 'objective'; }

    public function area(): string { return 'membership'; }

    public function icon(): string { return 'light-bulb'; }

    public function description(): ?string { return 'The numbered reasons to join (01, 02 …) in the “why join” block.'; }

    public function fields(?Model $record): array
    {
        return [
            Field::text('title')->required()->max(160),
            Field::textarea('description')->rows(3)->max(500),
        ];
    }

    public function emptyText(): string { return 'No objectives yet — the block is hidden until you add one.'; }
}
