<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Announcement;
use Illuminate\Database\Eloquent\Model;

class AnnouncementResource extends Resource
{
    public function key(): string { return 'announcements'; }

    public function model(): string { return Announcement::class; }

    public function label(): string { return 'Announcements'; }

    public function singular(): string { return 'announcement'; }

    public function area(): string { return 'announcements'; }

    public function icon(): string { return 'megaphone'; }

    public function description(): ?string { return 'Notices that scroll in the top bar of every page.'; }

    public function titleAttribute(): string { return 'text'; }

    public function subtitleFor(Model $record): ?string { return $record->getAttribute('url'); }

    public function fields(?Model $record): array
    {
        return [
            Field::textarea('text')->required()->rows(2)->max(500),
            Field::link('url', 'Link')->hint('Optional — makes the notice clickable.'),
        ];
    }
}
