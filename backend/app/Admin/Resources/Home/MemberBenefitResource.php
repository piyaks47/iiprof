<?php

namespace App\Admin\Resources\Home;

use App\Admin\Field;
use Illuminate\Database\Eloquent\Model;

class MemberBenefitResource extends HomeItemResource
{
    public function section(): string { return 'benefits'; }

    public function key(): string { return 'member-benefits'; }

    public function label(): string { return 'Membership benefits'; }

    public function singular(): string { return 'benefit'; }

    public function area(): string { return 'membership'; }

    public function icon(): string { return 'gift'; }

    public function description(): ?string { return 'The facilities members get, shown as an icon grid on the homepage.'; }

    public function fields(?Model $record): array
    {
        return [
            Field::text('title')->required()->max(120),
            Field::textarea('description')->rows(2)->max(400),
            Field::icon('icon')->required()->default('star'),
        ];
    }

    public function emptyText(): string { return 'No benefits yet — the block is hidden until you add one.'; }
}
