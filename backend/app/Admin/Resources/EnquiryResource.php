<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Enquiry;
use Illuminate\Database\Eloquent\Model;

class EnquiryResource extends Resource
{
    public function key(): string { return 'enquiries'; }

    public function model(): string { return Enquiry::class; }

    public function label(): string { return 'Enquiries'; }

    public function singular(): string { return 'enquiry'; }

    public function area(): string { return 'enquiries'; }

    public function icon(): string { return 'inbox'; }

    public function description(): ?string { return 'Messages sent with the “Enquire” form on the website.'; }

    public function titleFor(Model $record): string
    {
        return $record->getAttribute('name').' — '.($record->getAttribute('subject') ?: 'General enquiry');
    }

    public function subtitleFor(Model $record): ?string
    {
        return $record->getAttribute('email').' · '.$record->getAttribute('created_at')?->diffForHumans().' · '
            .\Illuminate\Support\Str::limit((string) $record->getAttribute('message'), 90);
    }

    public function badgesFor(Model $record): array
    {
        $status = (string) $record->getAttribute('status');

        return [[Enquiry::STATUSES[$status] ?? $status, match ($status) { 'new' => 'amber', 'closed' => 'green', default => 'sky' }]];
    }

    public function sortable(): bool { return false; }

    public function toggleable(): bool { return false; }

    public function canCreate(): bool { return false; }

    public function searchable(): array { return ['name', 'email', 'phone', 'subject', 'message']; }

    public function filters(): array
    {
        return ['status' => ['label' => 'All statuses', 'options' => Enquiry::STATUSES]];
    }

    public function fields(?Model $record): array
    {
        return [
            Field::text('name')->readonly(),
            Field::email('email')->readonly(),
            Field::text('phone')->readonly(),
            Field::text('subject')->readonly(),
            Field::textarea('message')->readonly()->wide(),
            Field::select('status')->required()->options(Enquiry::STATUSES),
            Field::textarea('notes', 'Internal notes')->rows(4)->hint('Only visible in the admin panel.'),
        ];
    }

    public function badge(): ?int
    {
        return Enquiry::query()->unread()->count() ?: null;
    }

    public function emptyText(): string { return 'No enquiries yet. They arrive here when visitors use the “Enquire” form.'; }
}
