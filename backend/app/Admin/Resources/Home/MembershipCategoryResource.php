<?php

namespace App\Admin\Resources\Home;

use App\Admin\Field;
use Illuminate\Database\Eloquent\Model;

class MembershipCategoryResource extends HomeItemResource
{
    public function section(): string { return 'membership'; }

    public function key(): string { return 'membership-categories'; }

    public function label(): string { return 'Membership categories'; }

    public function singular(): string { return 'category'; }

    public function area(): string { return 'membership'; }

    public function icon(): string { return 'identification'; }

    public function description(): ?string { return 'Cards for the membership grades on the homepage, each with its own button.'; }

    public function badgesFor(Model $record): array
    {
        return $record->getAttribute('value') ? [[(string) $record->getAttribute('value'), 'sky']] : [];
    }

    public function fields(?Model $record): array
    {
        return [
            Field::text('title', 'Name')->required()->max(120)->hint('e.g. “Fellow Member”.'),
            Field::text('value', 'Code')->max(30)->hint('Post-nominal letters, e.g. FMIIP.'),
            Field::text('subtitle', 'Who it is for')->max(200)->hint('One short line, e.g. “Age 30+, 5 years in the profession”.'),
            Field::textarea('description', 'Details')->rows(3)->max(500),
            Field::text('button_text', 'Button text')->max(80)->default('Apply now'),
            Field::link('button_url', 'Button link')->default('#register')->hint('#register opens the membership form.'),
        ];
    }

    public function emptyText(): string { return 'No categories yet — the block is hidden until you add one.'; }
}
