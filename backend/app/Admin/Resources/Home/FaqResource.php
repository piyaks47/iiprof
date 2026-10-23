<?php

namespace App\Admin\Resources\Home;

use App\Admin\Field;
use Illuminate\Database\Eloquent\Model;

class FaqResource extends HomeItemResource
{
    public function section(): string { return 'faq'; }

    public function key(): string { return 'faqs'; }

    public function label(): string { return 'FAQs'; }

    public function singular(): string { return 'question'; }

    public function icon(): string { return 'chat'; }

    public function description(): ?string { return 'Questions and answers, shown as an accordion on the homepage.'; }

    public function subtitleFor(Model $record): ?string
    {
        return \Illuminate\Support\Str::limit((string) $record->getAttribute('description'), 120);
    }

    public function fields(?Model $record): array
    {
        return [
            Field::text('title', 'Question')->required()->max(255),
            Field::markdown('description', 'Answer')->required()->rows(5)->hint('Markdown: **bold**, [link](/membership), - lists.'),
        ];
    }

    public function emptyText(): string { return 'No questions yet — the block is hidden until you add one.'; }
}
