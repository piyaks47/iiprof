<?php

namespace App\Admin\Resources\Membership;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\MembershipFee;
use Illuminate\Database\Eloquent\Model;

class MembershipFeeResource extends Resource
{
    public function key(): string { return 'membership-fees'; }

    public function model(): string { return MembershipFee::class; }

    public function label(): string { return 'Membership fees'; }

    public function singular(): string { return 'fee'; }

    public function area(): string { return 'membership'; }

    public function icon(): string { return 'banknotes'; }

    public function titleAttribute(): string { return 'grade'; }

    public function description(): ?string { return 'The fee table of the “how to apply” block. Amounts in rupees (e.g. 1500), or a word such as Free or On request.'; }

    public function subtitleFor(Model $record): ?string
    {
        return collect([
            $record->getAttribute('annual_fee') ? '1 year: '.$this->money($record->getAttribute('annual_fee')) : null,
            $record->getAttribute('lifetime_fee') ? 'Life time: '.$this->money($record->getAttribute('lifetime_fee')) : null,
            $record->getAttribute('note'),
        ])->filter()->implode(' · ');
    }

    public function badgesFor(Model $record): array
    {
        return $record->getAttribute('code') ? [[(string) $record->getAttribute('code'), 'sky']] : [];
    }

    public function fields(?Model $record): array
    {
        return [
            Field::text('grade', 'Membership grade')->required()->max(160),
            Field::text('code', 'Code')->max(30)->hint('e.g. MIIP.'),
            Field::text('annual_fee', 'Fee for 1 year')->max(60)->hint('Rupees, e.g. 1500 — or Free, On request.'),
            Field::text('lifetime_fee', 'Fee for life time')->max(60)->hint('Leave empty if there is no life-time option.'),
            Field::text('note')->max(255)->hint('Optional, e.g. “By invitation”.'),
            Field::link('button_url', 'Apply link')->hint('Optional; the table links to #register by default.'),
        ];
    }

    private function money(string $value): string
    {
        return is_numeric($value) ? '₹'.number_format((float) $value) : $value;
    }

    public function emptyText(): string { return 'No fees yet — the table is hidden.'; }
}
