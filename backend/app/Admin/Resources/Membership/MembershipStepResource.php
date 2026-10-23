<?php

namespace App\Admin\Resources\Membership;

use App\Admin\Field;
use App\Admin\Resources\Home\HomeItemResource;
use Illuminate\Database\Eloquent\Model;

class MembershipStepResource extends HomeItemResource
{
    public function section(): string { return 'membership_apply'; }

    public function key(): string { return 'membership-steps'; }

    public function label(): string { return 'How to apply'; }

    public function singular(): string { return 'step'; }

    public function area(): string { return 'membership'; }

    public function icon(): string { return 'queue'; }

    public function description(): ?string { return 'The steps to become a member, shown next to the fee table.'; }

    public function fields(?Model $record): array
    {
        return [
            Field::text('title')->required()->max(160),
            Field::textarea('description')->rows(3)->max(500),
            Field::icon('icon')->default('check'),
        ];
    }

    public function emptyText(): string { return 'No steps yet.'; }
}
