<?php

namespace App\Admin\Resources\Home;

use App\Admin\Field;
use Illuminate\Database\Eloquent\Model;

class PartnerResource extends HomeItemResource
{
    public function section(): string { return 'partners'; }

    public function key(): string { return 'partners'; }

    public function label(): string { return 'Registrations & partners'; }

    public function singular(): string { return 'entry'; }

    public function icon(): string { return 'shield'; }

    public function description(): ?string { return 'Registrations, affiliations and partners, shown as a strip of logos (or names) on the homepage.'; }

    public function fields(?Model $record): array
    {
        return [
            Field::text('title', 'Name')->required()->max(120),
            Field::text('subtitle', 'Detail')->max(160)->hint('e.g. “Section 80G approval”.'),
            Field::image('image', 'Logo')->hint('Optional. PNG or SVG with a transparent background; without a logo the name is shown.'),
            Field::link('button_url', 'Link')->hint('Optional, e.g. /credibility or the partner’s website.'),
        ];
    }

    public function emptyText(): string { return 'Nothing here yet — the block is hidden until you add an entry.'; }
}
